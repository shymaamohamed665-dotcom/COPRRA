#!/usr/bin/env python3
"""
Task 4 Intelligent Execution Script
مشروع COPRRA - تنفيذ ذكي لجميع الاختبارات والأدوات (450 عنصر)
"""

import os
import re
import sys
import json
import time
import subprocess
from pathlib import Path
from datetime import datetime
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import List, Dict, Tuple, Optional

# Configuration
BATCH_SIZE = 10
MAX_WORKERS = 10
TIMEOUT_SECONDS = 300
REPORTS_DIR = Path("reports/task4_execution")
INVENTORY_FILE = Path("FRESH_COMPREHENSIVE_TESTS_AND_TOOLS_INVENTORY_2025.md")

# Colors
class Colors:
    RED = '\033[0;31m'
    GREEN = '\033[0;32m'
    YELLOW = '\033[1;33m'
    BLUE = '\033[0;34m'
    MAGENTA = '\033[0;35m'
    CYAN = '\033[0;36m'
    NC = '\033[0m'  # No Color

class TestItem:
    """Represents a single test/tool item"""
    def __init__(self, number: int, name: str, command: str, description: str, 
                 criteria: str, output_file: str):
        self.number = number
        self.name = name
        self.command = command
        self.description = description
        self.criteria = criteria
        self.output_file = output_file
        self.status = "pending"
        self.exit_code = None
        self.execution_time = 0.0
        self.error_message = ""

class Task4Executor:
    """Main executor for Task 4"""
    
    def __init__(self):
        self.tests: List[TestItem] = []
        self.results = {
            'total': 0,
            'passed': 0,
            'failed': 0,
            'skipped': 0,
            'errors': 0
        }
        self.start_time = None
        self.end_time = None
        
    def parse_inventory(self) -> None:
        """Parse the comprehensive inventory file"""
        print(f"{Colors.BLUE}=== قراءة ملف القائمة الشاملة ==={Colors.NC}")
        
        if not INVENTORY_FILE.exists():
            print(f"{Colors.RED}✗ خطأ: لم يتم العثور على ملف القائمة{Colors.NC}")
            sys.exit(1)
        
        with open(INVENTORY_FILE, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Parse test items using regex
        pattern = r'####\s+(\d+)\.\s+(.+?)\n-\s+\*\*الأمر\*\*:\s+`(.+?)`\n-\s+\*\*الوصف\*\*:\s+(.+?)\n-\s+\*\*المعايير\*\*:\s+(.+?)\n-\s+\*\*الإخراج\*\*:\s+`(.+?)`'
        matches = re.findall(pattern, content, re.MULTILINE | re.DOTALL)
        
        for match in matches:
            number = int(match[0])
            name = match[1].strip()
            command = match[2].strip()
            description = match[3].strip()
            criteria = match[4].strip()
            output_file = match[5].strip()
            
            test = TestItem(number, name, command, description, criteria, output_file)
            self.tests.append(test)
        
        self.results['total'] = len(self.tests)
        print(f"{Colors.GREEN}✓ تم قراءة {len(self.tests)} عنصر من القائمة{Colors.NC}")
    
    def setup_environment(self) -> None:
        """Setup execution environment"""
        print(f"{Colors.BLUE}=== تهيئة بيئة التنفيذ ==={Colors.NC}")
        
        # Create directories
        REPORTS_DIR.mkdir(parents=True, exist_ok=True)
        (REPORTS_DIR / "batch_logs").mkdir(exist_ok=True)
        (REPORTS_DIR / "individual_outputs").mkdir(exist_ok=True)
        (REPORTS_DIR / "json_results").mkdir(exist_ok=True)
        
        print(f"{Colors.GREEN}✓ تم تهيئة البيئة بنجاح{Colors.NC}")
    
    def execute_test(self, test: TestItem) -> TestItem:
        """Execute a single test"""
        output_path = REPORTS_DIR / "individual_outputs" / test.output_file
        output_path.parent.mkdir(parents=True, exist_ok=True)
        
        start_time = time.time()
        
        try:
            # Execute command
            result = subprocess.run(
                test.command,
                shell=True,
                capture_output=True,
                text=True,
                timeout=TIMEOUT_SECONDS,
                cwd=Path.cwd()
            )
            
            test.execution_time = time.time() - start_time
            test.exit_code = result.returncode
            
            # Write output
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(f"Test #{test.number}: {test.name}\n")
                f.write(f"Command: {test.command}\n")
                f.write(f"Execution Time: {test.execution_time:.2f}s\n")
                f.write(f"Exit Code: {test.exit_code}\n")
                f.write("=" * 80 + "\n")
                f.write("STDOUT:\n")
                f.write(result.stdout)
                f.write("\n" + "=" * 80 + "\n")
                f.write("STDERR:\n")
                f.write(result.stderr)
            
            if result.returncode == 0:
                test.status = "passed"
            else:
                test.status = "failed"
                test.error_message = result.stderr[:200] if result.stderr else "Unknown error"
                
        except subprocess.TimeoutExpired:
            test.execution_time = TIMEOUT_SECONDS
            test.status = "timeout"
            test.error_message = f"Timeout after {TIMEOUT_SECONDS}s"
            
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(f"Test #{test.number}: {test.name}\n")
                f.write(f"Status: TIMEOUT after {TIMEOUT_SECONDS}s\n")
                
        except Exception as e:
            test.execution_time = time.time() - start_time
            test.status = "error"
            test.error_message = str(e)
            
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(f"Test #{test.number}: {test.name}\n")
                f.write(f"Status: ERROR\n")
                f.write(f"Error: {str(e)}\n")
        
        return test
    
    def execute_batch(self, batch_number: int, batch_tests: List[TestItem]) -> None:
        """Execute a batch of tests in parallel"""
        batch_start = batch_tests[0].number
        batch_end = batch_tests[-1].number
        
        print(f"\n{Colors.YELLOW}=== الدفعة #{batch_number + 1}: الاختبارات {batch_start}-{batch_end} ==={Colors.NC}")
        
        batch_results = {'passed': 0, 'failed': 0, 'skipped': 0, 'errors': 0}
        
        # Execute tests in parallel
        with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
            futures = {executor.submit(self.execute_test, test): test for test in batch_tests}
            
            for future in as_completed(futures):
                test = future.result()
                
                # Update counters
                if test.status == "passed":
                    batch_results['passed'] += 1
                    self.results['passed'] += 1
                    status_icon = f"{Colors.GREEN}✓{Colors.NC}"
                elif test.status == "failed":
                    batch_results['failed'] += 1
                    self.results['failed'] += 1
                    status_icon = f"{Colors.RED}✗{Colors.NC}"
                elif test.status == "timeout":
                    batch_results['errors'] += 1
                    self.results['errors'] += 1
                    status_icon = f"{Colors.YELLOW}⏱{Colors.NC}"
                else:
                    batch_results['errors'] += 1
                    self.results['errors'] += 1
                    status_icon = f"{Colors.RED}⚠{Colors.NC}"
                
                print(f"  {status_icon} #{test.number:03d} {test.name[:60]} ({test.execution_time:.1f}s)")
        
        # Save batch results
        batch_log = REPORTS_DIR / "batch_logs" / f"batch_{batch_number + 1}.json"
        with open(batch_log, 'w', encoding='utf-8') as f:
            json.dump({
                'batch_number': batch_number + 1,
                'tests': [t.__dict__ for t in batch_tests],
                'results': batch_results
            }, f, indent=2, ensure_ascii=False)
        
        print(f"{Colors.CYAN}الدفعة #{batch_number + 1}: ✓ {batch_results['passed']} | "
              f"✗ {batch_results['failed']} | ⚠ {batch_results['errors']}{Colors.NC}")
    
    def execute_all(self) -> None:
        """Execute all tests in batches"""
        print(f"\n{Colors.BLUE}=== بدء تنفيذ جميع الاختبارات ==={Colors.NC}")
        print(f"إجمالي الاختبارات: {len(self.tests)}")
        print(f"حجم الدفعة: {BATCH_SIZE}")
        print(f"العمليات المتوازية: {MAX_WORKERS}")
        
        self.start_time = datetime.now()
        
        # Execute in batches
        for i in range(0, len(self.tests), BATCH_SIZE):
            batch_tests = self.tests[i:i + BATCH_SIZE]
            batch_number = i // BATCH_SIZE
            self.execute_batch(batch_number, batch_tests)
            
            # Progress
            progress = min(i + BATCH_SIZE, len(self.tests))
            percentage = (progress * 100) // len(self.tests)
            print(f"{Colors.MAGENTA}التقدم الإجمالي: {progress}/{len(self.tests)} ({percentage}%){Colors.NC}")
            
            # Small delay between batches
            if i + BATCH_SIZE < len(self.tests):
                time.sleep(1)
        
        self.end_time = datetime.now()
    
    def generate_report(self) -> None:
        """Generate final comprehensive report"""
        print(f"\n{Colors.BLUE}=== إنشاء التقرير النهائي ==={Colors.NC}")
        
        duration = (self.end_time - self.start_time).total_seconds()
        success_rate = (self.results['passed'] * 100) // self.results['total'] if self.results['total'] > 0 else 0
        
        report_path = REPORTS_DIR / "TASK_4_EXECUTION_REPORT.md"
        
        with open(report_path, 'w', encoding='utf-8') as f:
            f.write("# تقرير تنفيذ Task 4\n")
            f.write("## مشروع COPRRA - تنفيذ شامل لجميع الاختبارات والأدوات\n\n")
            f.write(f"**تاريخ التنفيذ**: {self.start_time.strftime('%Y-%m-%d %H:%M:%S')}\n\n")
            f.write("---\n\n")
            f.write("## 📊 ملخص النتائج\n\n")
            f.write(f"- **إجمالي الاختبارات**: {self.results['total']}\n")
            f.write(f"- **✓ نجح**: {self.results['passed']}\n")
            f.write(f"- **✗ فشل**: {self.results['failed']}\n")
            f.write(f"- **⚠ أخطاء**: {self.results['errors']}\n")
            f.write(f"- **⊘ تم تخطيه**: {self.results['skipped']}\n\n")
            f.write(f"**نسبة النجاح**: {success_rate}%\n\n")
            f.write(f"**مدة التنفيذ**: {duration:.2f} ثانية ({duration/60:.2f} دقيقة)\n\n")
            f.write("---\n\n")
            
            if success_rate >= 90:
                f.write("## ✅ الحالة: ممتاز\n\n")
                f.write("نسبة النجاح أعلى من 90% - المشروع في حالة ممتازة!\n\n")
            elif success_rate >= 80:
                f.write("## ✓ الحالة: جيد\n\n")
                f.write("نسبة النجاح أعلى من 80% - المشروع في حالة جيدة.\n\n")
            else:
                f.write("## ⚠ الحالة: يحتاج إلى تحسين\n\n")
                f.write("نسبة النجاح أقل من 80% - يُنصح بمراجعة الاختبارات الفاشلة.\n\n")
        
        print(f"{Colors.GREEN}✓ تم إنشاء التقرير: {report_path}{Colors.NC}")
    
    def run(self) -> None:
        """Main execution flow"""
        print(f"{Colors.BLUE}")
        print("=" * 80)
        print("Task 4 - تنفيذ جميع الاختبارات والأدوات")
        print("مشروع COPRRA")
        print("=" * 80)
        print(f"{Colors.NC}\n")
        
        self.parse_inventory()
        self.setup_environment()
        self.execute_all()
        self.generate_report()
        
        print(f"\n{Colors.GREEN}✓ اكتمل تنفيذ Task 4 بنجاح{Colors.NC}")
        print(f"{Colors.CYAN}نسبة النجاح: {(self.results['passed'] * 100) // self.results['total']}%{Colors.NC}")

if __name__ == "__main__":
    executor = Task4Executor()
    executor.run()

