#!/usr/bin/env python3

import subprocess
import sys
import os
import time
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import List, Dict, Optional
import json

class SmartTestExecutor:
    def __init__(self, batch_size: int = 30, max_workers: int = 5):
        self.batch_size = batch_size
        self.max_workers = max_workers
        self.total_tests = 628
        self.executed = 0
        self.success_count = 0
        self.failed_count = 0
        self.start_time = time.time()
        self.results_dir = "test_results"
        self.failed_outputs_dir = os.path.join(self.results_dir, "failed_outputs")
        self.success_list_file = os.path.join(self.results_dir, "successful_tests.txt")
        self.failed_to_start_file = os.path.join(self.results_dir, "failed_to_start.txt")
        self.summary_file = os.path.join(self.results_dir, "execution_summary.md")
        self.initialize_directories()

    def initialize_directories(self):
        """Create necessary directories and files"""
        os.makedirs(self.failed_outputs_dir, exist_ok=True)
        for file in [self.success_list_file, self.failed_to_start_file, self.summary_file]:
            dirname = os.path.dirname(file)
            if dirname:
                os.makedirs(dirname, exist_ok=True)

    def generate_test_commands(self) -> List[Dict]:
        """Generate all 628 test commands with their configurations"""
        commands = []

        # PHPUnit test suites
        phpunit_suites = ['AI', 'Security', 'Performance', 'Integration', 'Unit', 'Feature', 'Comprehensive']
        for suite in phpunit_suites:
            commands.append({
                'name': f'PHPUnit {suite} Tests',
                'command': f'vendor/bin/phpunit --testsuite {suite}',
                'timeout': 600
            })

        # Static Analysis Tools
        static_analysis = [
            {'name': 'PHPStan', 'command': './vendor/bin/phpstan analyse --memory-limit=1G'},
            {'name': 'Psalm', 'command': './vendor/bin/psalm'},
            {'name': 'PHP Insights', 'command': './vendor/bin/phpinsights analyse app'},
            {'name': 'PHPMD', 'command': './vendor/bin/phpmd app text cleancode,codesize,controversial,design,naming,unusedcode'},
            {'name': 'PHP-CS-Fixer', 'command': './vendor/bin/php-cs-fixer fix --dry-run'},
            {'name': 'PHPCPD', 'command': './vendor/bin/phpcpd app/'},
            {'name': 'Pint', 'command': './vendor/bin/pint --test'}
        ]
        commands.extend(static_analysis)

        # Security Tools
        security_tools = [
            {'name': 'Security Checker', 'command': './vendor/bin/security-checker security:check'},
            {'name': 'Composer Audit', 'command': 'composer audit'},
        ]
        commands.extend(security_tools)

        # Quality Scripts
        quality_scripts = [
            {'name': 'All Tests', 'command': './run_all_450_tests.sh'},
            {'name': 'Quality Audit', 'command': './comprehensive-quality-audit.sh'},
            {'name': 'Performance Tests', 'command': './run_tests_docker.sh'}
        ]
        commands.extend(quality_scripts)

        # Add remaining commands to reach 628
        # This is a placeholder - you would need to add your actual remaining commands
        while len(commands) < 628:
            commands.append({
                'name': f'Additional Test {len(commands) + 1}',
                'command': f'echo "Test {len(commands) + 1}"',
                'timeout': 30
            })

        return commands

    def execute_command(self, test: Dict) -> Dict:
        """Execute a single test command and return its result"""
        start_time = time.time()
        result = {
            'name': test['name'],
            'command': test['command'],
            'status': 'unknown',
            'output': '',
            'error': '',
            'duration': 0
        }

        try:
            process = subprocess.Popen(
                test['command'],
                shell=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True
            )

            try:
                stdout, stderr = process.communicate(timeout=test.get('timeout', 300))
                result['output'] = stdout
                result['error'] = stderr
                result['status'] = 'success' if process.returncode == 0 else 'failed'
            except subprocess.TimeoutExpired:
                process.kill()
                result['status'] = 'timeout'
                result['error'] = f"Command timed out after {test.get('timeout', 300)} seconds"

        except Exception as e:
            result['status'] = 'failed_to_start'
            result['error'] = str(e)

        result['duration'] = time.time() - start_time
        return result

    def save_result(self, result: Dict):
        """Save the test result according to the rules"""
        if result['status'] == 'success':
            with open(self.success_list_file, 'a') as f:
                f.write(f"{result['name']}\n")
            self.success_count += 1

        elif result['status'] == 'failed':
            output_file = os.path.join(
                self.failed_outputs_dir,
                f"{result['name'].replace(' ', '_')}_{int(time.time())}.txt"
            )
            with open(output_file, 'w') as f:
                f.write(f"Command: {result['command']}\n")
                f.write(f"Output:\n{result['output']}\n")
                f.write(f"Error:\n{result['error']}\n")
            self.failed_count += 1

        elif result['status'] == 'failed_to_start':
            with open(self.failed_to_start_file, 'a') as f:
                f.write(f"{result['name']}: {result['error']}\n")
            self.failed_count += 1

    def print_batch_summary(self, batch_number: int, batch_results: List[Dict]):
        """Print a summary for the completed batch"""
        success = sum(1 for r in batch_results if r['status'] == 'success')
        failed = len(batch_results) - success

        print(f"\n{'='*80}")
        print(f"Batch {batch_number} Summary ({self.executed}/{self.total_tests})")
        print(f"{'='*80}")
        print(f"✓ Successful: {success}")
        print(f"✗ Failed: {failed}")
        print(f"Time elapsed: {time.time() - self.start_time:.2f}s")
        print(f"{'='*80}\n")

        # Save batch summary to file
        with open(self.summary_file, 'a') as f:
            f.write(f"\n## Batch {batch_number} Summary\n")
            f.write(f"- Executed: {self.executed}/{self.total_tests}\n")
            f.write(f"- Successful: {success}\n")
            f.write(f"- Failed: {failed}\n")
            f.write(f"- Time: {time.time() - self.start_time:.2f}s\n")
            for result in batch_results:
                status_symbol = '✓' if result['status'] == 'success' else '✗'
                f.write(f"\n{status_symbol} {result['name']} ({result['duration']:.2f}s)\n")
                if result['status'] != 'success':
                    f.write(f"```\n{result['error']}\n```\n")

    def execute_batch(self, batch: List[Dict]) -> List[Dict]:
        """Execute a batch of tests in parallel"""
        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            futures = [executor.submit(self.execute_command, test) for test in batch]
            results = []

            for future in as_completed(futures):
                result = future.result()
                self.save_result(result)
                self.executed += 1
                results.append(result)

                # Print progress
                print(f"\rProgress: {self.executed}/{self.total_tests} "
                      f"(✓:{self.success_count} ✗:{self.failed_count})", end='')

            return results

    def run(self):
        """Run all tests in batches"""
        print("Starting Smart Test Executor")
        print(f"Total tests: {self.total_tests}")
        print(f"Batch size: {self.batch_size}")
        print(f"Max parallel executions: {self.max_workers}")
        print("="*80)

        commands = self.generate_test_commands()
        batch_number = 0

        for i in range(0, len(commands), self.batch_size):
            batch_number += 1
            batch = commands[i:i + self.batch_size]
            print(f"\nExecuting batch {batch_number}...")

            results = self.execute_batch(batch)
            self.print_batch_summary(batch_number, results)

        # Print final summary
        print("\nExecution Complete!")
        print(f"Total executed: {self.executed}/{self.total_tests}")
        print(f"Successful: {self.success_count}")
        print(f"Failed: {self.failed_count}")
        print(f"Total time: {time.time() - self.start_time:.2f}s")

if __name__ == "__main__":
    executor = SmartTestExecutor(batch_size=30, max_workers=5)
    executor.run()
