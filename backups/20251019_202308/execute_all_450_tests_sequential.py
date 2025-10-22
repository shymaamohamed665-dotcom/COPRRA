#!/usr/bin/env python3
"""
تنفيذ جميع الـ 450 اختبار/أداة بشكل تسلسلي
حفظ المخرجات السلبية فقط (أخطاء ومشاكل)
"""

import os
import re
import sys
import subprocess
import time
from pathlib import Path
from datetime import datetime

# Configuration
INVENTORY_FILE = Path("FRESH_COMPREHENSIVE_TESTS_AND_TOOLS_INVENTORY_2025.md")
RESULTS_FILE = Path("TASK_4_NEGATIVE_OUTPUTS_ONLY.md")
TIMEOUT_SECONDS = 300

class TestExecutor:
    def __init__(self):
        self.tests = []
        self.negative_outputs = []
        self.failed_tools = []
        self.total_executed = 0
        self.total_passed = 0
        self.total_failed = 0
        self.start_time = None
        
    def parse_inventory(self):
        """قراءة وتحليل ملف القائمة الشاملة"""
        print("📖 قراءة ملف القائمة الشاملة...")
        
        with open(INVENTORY_FILE, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Pattern to extract test items
        pattern = r'####\s+(\d+)\.\s+(.+?)\n-\s+\*\*الأمر\*\*:\s+`(.+?)`\n-\s+\*\*الوصف\*\*:\s+(.+?)\n-\s+\*\*المعايير\*\*:\s+(.+?)\n-\s+\*\*الإخراج\*\*:\s+`(.+?)`'
        matches = re.findall(pattern, content, re.MULTILINE | re.DOTALL)
        
        for match in matches:
            self.tests.append({
                'number': int(match[0]),
                'name': match[1].strip(),
                'command': match[2].strip(),
                'description': match[3].strip(),
                'criteria': match[4].strip(),
                'output_file': match[5].strip()
            })
        
        print(f"✅ تم قراءة {len(self.tests)} اختبار/أداة\n")
        
    def execute_test(self, test):
        """تنفيذ اختبار واحد"""
        num = test['number']
        name = test['name']
        cmd = test['command']
        
        print(f"[{num:03d}/450] تنفيذ: {name[:60]}...", end=' ', flush=True)
        
        try:
            result = subprocess.run(
                cmd,
                shell=True,
                capture_output=True,
                text=True,
                timeout=TIMEOUT_SECONDS,
                cwd=Path.cwd()
            )
            
            # Check if test passed or failed
            has_errors = False
            error_indicators = [
                'error', 'failed', 'failure', 'exception', 'fatal',
                'warning', 'deprecated', 'notice', 'violation',
                'found', 'issues', 'problems', 'bugs'
            ]
            
            output_combined = (result.stdout + result.stderr).lower()
            
            # Check exit code
            if result.returncode != 0:
                has_errors = True
            
            # Check for error indicators in output
            for indicator in error_indicators:
                if indicator in output_combined:
                    has_errors = True
                    break
            
            if has_errors:
                print("❌ مشاكل")
                self.total_failed += 1
                self.negative_outputs.append({
                    'number': num,
                    'name': name,
                    'command': cmd,
                    'exit_code': result.returncode,
                    'stdout': result.stdout,
                    'stderr': result.stderr
                })
            else:
                print("✅")
                self.total_passed += 1
                
        except subprocess.TimeoutExpired:
            print("⏱️ Timeout")
            self.total_failed += 1
            self.failed_tools.append({
                'number': num,
                'name': name,
                'command': cmd,
                'reason': f'Timeout بعد {TIMEOUT_SECONDS} ثانية'
            })
            
        except FileNotFoundError:
            print("❌ الأداة غير موجودة")
            self.total_failed += 1
            self.failed_tools.append({
                'number': num,
                'name': name,
                'command': cmd,
                'reason': 'الأداة أو الأمر غير موجود'
            })
            
        except Exception as e:
            print(f"❌ خطأ: {str(e)[:30]}")
            self.total_failed += 1
            self.failed_tools.append({
                'number': num,
                'name': name,
                'command': cmd,
                'reason': str(e)
            })
        
        self.total_executed += 1
        
    def execute_all(self):
        """تنفيذ جميع الاختبارات"""
        print("🚀 بدء تنفيذ جميع الاختبارات...\n")
        print("=" * 80)
        
        self.start_time = datetime.now()
        
        for test in self.tests:
            self.execute_test(test)
            
            # Progress indicator every 50 tests
            if self.total_executed % 50 == 0:
                elapsed = (datetime.now() - self.start_time).total_seconds()
                avg_time = elapsed / self.total_executed
                remaining = (450 - self.total_executed) * avg_time
                print(f"\n📊 التقدم: {self.total_executed}/450 | "
                      f"✅ {self.total_passed} | ❌ {self.total_failed} | "
                      f"⏱️ متبقي: {remaining/60:.1f} دقيقة\n")
        
        print("=" * 80)
        print("\n✅ اكتمل تنفيذ جميع الاختبارات!\n")
        
    def save_results(self):
        """حفظ النتائج في ملف"""
        print("💾 حفظ النتائج...")
        
        end_time = datetime.now()
        duration = (end_time - self.start_time).total_seconds()
        
        with open(RESULTS_FILE, 'w', encoding='utf-8') as f:
            # Header
            f.write("# تقرير المخرجات السلبية - Task 4\n")
            f.write("## مشروع COPRRA - المشاكل والأخطاء المكتشفة\n\n")
            f.write(f"**تاريخ التنفيذ**: {self.start_time.strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"**مدة التنفيذ**: {duration:.2f} ثانية ({duration/60:.2f} دقيقة)\n\n")
            f.write("---\n\n")
            
            # Summary
            f.write("## 📊 ملخص النتائج\n\n")
            f.write(f"- **إجمالي الاختبارات المنفذة**: {self.total_executed}\n")
            f.write(f"- **✅ نجح بدون مشاكل**: {self.total_passed}\n")
            f.write(f"- **❌ يحتوي على مشاكل**: {self.total_failed}\n")
            f.write(f"- **نسبة النجاح**: {(self.total_passed * 100 / self.total_executed):.1f}%\n\n")
            f.write("---\n\n")
            
            # Section 1: Negative Outputs
            f.write("## 🔴 القسم الأول: المخرجات السلبية (مشاكل وأخطاء)\n\n")
            f.write(f"**عدد الاختبارات التي تحتوي على مشاكل**: {len(self.negative_outputs)}\n\n")
            
            if self.negative_outputs:
                for i, output in enumerate(self.negative_outputs, 1):
                    f.write(f"### {i}. [{output['number']:03d}] {output['name']}\n\n")
                    f.write(f"**الأمر**: `{output['command']}`\n\n")
                    f.write(f"**Exit Code**: {output['exit_code']}\n\n")
                    
                    if output['stderr']:
                        f.write("**STDERR (الأخطاء)**:\n```\n")
                        f.write(output['stderr'][:2000])  # First 2000 chars
                        if len(output['stderr']) > 2000:
                            f.write("\n... (تم اقتطاع الباقي)")
                        f.write("\n```\n\n")
                    
                    if output['stdout']:
                        f.write("**STDOUT (المخرجات)**:\n```\n")
                        f.write(output['stdout'][:2000])  # First 2000 chars
                        if len(output['stdout']) > 2000:
                            f.write("\n... (تم اقتطاع الباقي)")
                        f.write("\n```\n\n")
                    
                    f.write("---\n\n")
            else:
                f.write("✅ **لا توجد مخرجات سلبية - جميع الاختبارات نجحت!**\n\n")
            
            # Section 2: Failed Tools
            f.write("## ⚠️ القسم الثاني: الأدوات/الاختبارات التي فشل تشغيلها\n\n")
            f.write(f"**عدد الأدوات التي فشل تشغيلها**: {len(self.failed_tools)}\n\n")
            
            if self.failed_tools:
                for i, tool in enumerate(self.failed_tools, 1):
                    f.write(f"### {i}. [{tool['number']:03d}] {tool['name']}\n\n")
                    f.write(f"**الأمر**: `{tool['command']}`\n\n")
                    f.write(f"**السبب**: {tool['reason']}\n\n")
                    f.write("---\n\n")
            else:
                f.write("✅ **جميع الأدوات تعمل بشكل صحيح!**\n\n")
            
            # Footer
            f.write("---\n\n")
            f.write("## 🎯 الخلاصة\n\n")
            
            if self.total_failed == 0:
                f.write("✅ **ممتاز!** جميع الاختبارات نجحت بدون أي مشاكل.\n\n")
            elif self.total_failed <= 45:  # 10%
                f.write("✓ **جيد جداً!** نسبة المشاكل أقل من 10%.\n\n")
            elif self.total_failed <= 90:  # 20%
                f.write("⚠️ **مقبول** - يوجد بعض المشاكل التي تحتاج إلى مراجعة.\n\n")
            else:
                f.write("❌ **يحتاج إلى تحسين** - عدد كبير من المشاكل.\n\n")
            
            f.write(f"**تاريخ الانتهاء**: {end_time.strftime('%Y-%m-%d %H:%M:%S')}\n")
        
        print(f"✅ تم حفظ النتائج في: {RESULTS_FILE}\n")
        
    def run(self):
        """التنفيذ الرئيسي"""
        print("\n" + "=" * 80)
        print("🚀 Task 4 - تنفيذ جميع الـ 450 اختبار/أداة")
        print("مشروع COPRRA")
        print("=" * 80 + "\n")
        
        self.parse_inventory()
        self.execute_all()
        self.save_results()
        
        print("=" * 80)
        print("✅ اكتمل Task 4 بنجاح!")
        print(f"📊 النتائج: ✅ {self.total_passed} | ❌ {self.total_failed}")
        print(f"📄 التقرير: {RESULTS_FILE}")
        print("=" * 80 + "\n")

if __name__ == "__main__":
    executor = TestExecutor()
    executor.run()

