#!/bin/bash

################################################################################
# تنفيذ جميع الـ 450 اختبار/أداة بشكل تسلسلي
# حفظ المخرجات السلبية فقط
################################################################################

set +e  # Don't exit on error

# Files
INVENTORY="FRESH_COMPREHENSIVE_TESTS_AND_TOOLS_INVENTORY_2025.md"
OUTPUT="TASK_4_NEGATIVE_OUTPUTS_ONLY.md"
TEMP_DIR="temp_task4"

# Counters
TOTAL=450
PASSED=0
FAILED=0
TOOL_ERRORS=0

# Arrays to store results
declare -a NEGATIVE_OUTPUTS
declare -a FAILED_TOOLS

# Create temp directory
mkdir -p "$TEMP_DIR"

# Start time
START_TIME=$(date +%s)

echo "================================================================================"
echo "🚀 Task 4 - تنفيذ جميع الـ 450 اختبار/أداة"
echo "مشروع COPRRA"
echo "================================================================================"
echo ""
echo "📖 قراءة الأوامر من الملف..."

# Extract commands using grep
grep -oP '(?<=\*\*الأمر\*\*: `).*(?=`)' "$INVENTORY" > "$TEMP_DIR/commands.txt"

# Count commands
CMD_COUNT=$(wc -l < "$TEMP_DIR/commands.txt")
echo "✅ تم استخراج $CMD_COUNT أمر"
echo ""
echo "🚀 بدء التنفيذ..."
echo "================================================================================"
echo ""

# Read commands and execute
COUNTER=1
while IFS= read -r cmd; do
    printf "[%03d/%03d] تنفيذ: %s... " "$COUNTER" "$TOTAL" "${cmd:0:50}"
    
    # Execute command
    OUTPUT_FILE="$TEMP_DIR/test_${COUNTER}_output.txt"
    EXIT_CODE=0
    
    timeout 300 bash -c "$cmd" > "$OUTPUT_FILE" 2>&1 || EXIT_CODE=$?
    
    # Check result
    HAS_ERROR=0
    
    # Check exit code
    if [ $EXIT_CODE -ne 0 ]; then
        HAS_ERROR=1
    fi
    
    # Check for error indicators in output
    if [ -f "$OUTPUT_FILE" ]; then
        if grep -qiE '(error|failed|failure|exception|fatal|warning|deprecated|violation|found.*issues?|found.*problems?|found.*bugs?)' "$OUTPUT_FILE"; then
            HAS_ERROR=1
        fi
    fi
    
    if [ $HAS_ERROR -eq 1 ]; then
        echo "❌"
        ((FAILED++))
        # Store negative output
        echo "TEST_${COUNTER}|${cmd}|${EXIT_CODE}|${OUTPUT_FILE}" >> "$TEMP_DIR/negative_outputs.txt"
    else
        echo "✅"
        ((PASSED++))
        # Remove output file for passed tests
        rm -f "$OUTPUT_FILE"
    fi
    
    # Progress indicator every 50 tests
    if [ $((COUNTER % 50)) -eq 0 ]; then
        ELAPSED=$(($(date +%s) - START_TIME))
        AVG_TIME=$((ELAPSED / COUNTER))
        REMAINING=$(( (TOTAL - COUNTER) * AVG_TIME ))
        echo ""
        echo "📊 التقدم: $COUNTER/$TOTAL | ✅ $PASSED | ❌ $FAILED | ⏱️ متبقي: $((REMAINING / 60)) دقيقة"
        echo ""
    fi
    
    ((COUNTER++))
done < "$TEMP_DIR/commands.txt"

# End time
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo ""
echo "================================================================================"
echo "✅ اكتمل التنفيذ!"
echo "================================================================================"
echo ""
echo "📊 النتائج:"
echo "  - إجمالي الاختبارات: $TOTAL"
echo "  - ✅ نجح: $PASSED"
echo "  - ❌ فشل: $FAILED"
echo "  - ⏱️ المدة: $((DURATION / 60)) دقيقة و $((DURATION % 60)) ثانية"
echo ""

# Generate report
echo "💾 إنشاء التقرير..."

cat > "$OUTPUT" << 'HEADER'
# تقرير المخرجات السلبية - Task 4
## مشروع COPRRA - المشاكل والأخطاء المكتشفة

HEADER

echo "**تاريخ التنفيذ**: $(date '+%Y-%m-%d %H:%M:%S')" >> "$OUTPUT"
echo "**مدة التنفيذ**: $DURATION ثانية ($((DURATION / 60)) دقيقة)" >> "$OUTPUT"
echo "" >> "$OUTPUT"
echo "---" >> "$OUTPUT"
echo "" >> "$OUTPUT"

# Summary
cat >> "$OUTPUT" << SUMMARY
## 📊 ملخص النتائج

- **إجمالي الاختبارات المنفذة**: $TOTAL
- **✅ نجح بدون مشاكل**: $PASSED
- **❌ يحتوي على مشاكل**: $FAILED
- **نسبة النجاح**: $(( PASSED * 100 / TOTAL ))%

---

SUMMARY

# Section 1: Negative Outputs
echo "## 🔴 القسم الأول: المخرجات السلبية (مشاكل وأخطاء)" >> "$OUTPUT"
echo "" >> "$OUTPUT"
echo "**عدد الاختبارات التي تحتوي على مشاكل**: $FAILED" >> "$OUTPUT"
echo "" >> "$OUTPUT"

if [ -f "$TEMP_DIR/negative_outputs.txt" ]; then
    ITEM_NUM=1
    while IFS='|' read -r test_id cmd exit_code output_file; do
        echo "### $ITEM_NUM. $test_id" >> "$OUTPUT"
        echo "" >> "$OUTPUT"
        echo "**الأمر**: \`$cmd\`" >> "$OUTPUT"
        echo "" >> "$OUTPUT"
        echo "**Exit Code**: $exit_code" >> "$OUTPUT"
        echo "" >> "$OUTPUT"
        
        if [ -f "$output_file" ]; then
            echo "**المخرجات**:" >> "$OUTPUT"
            echo "\`\`\`" >> "$OUTPUT"
            head -n 100 "$output_file" >> "$OUTPUT"
            echo "\`\`\`" >> "$OUTPUT"
            echo "" >> "$OUTPUT"
        fi
        
        echo "---" >> "$OUTPUT"
        echo "" >> "$OUTPUT"
        ((ITEM_NUM++))
    done < "$TEMP_DIR/negative_outputs.txt"
else
    echo "✅ **لا توجد مخرجات سلبية - جميع الاختبارات نجحت!**" >> "$OUTPUT"
    echo "" >> "$OUTPUT"
fi

# Section 2: Failed Tools
echo "## ⚠️ القسم الثاني: الأدوات/الاختبارات التي فشل تشغيلها" >> "$OUTPUT"
echo "" >> "$OUTPUT"

if [ -f "$TEMP_DIR/failed_tools.txt" ]; then
    TOOL_COUNT=$(wc -l < "$TEMP_DIR/failed_tools.txt")
    echo "**عدد الأدوات التي فشل تشغيلها**: $TOOL_COUNT" >> "$OUTPUT"
    echo "" >> "$OUTPUT"
    
    TOOL_NUM=1
    while IFS='|' read -r test_id cmd reason; do
        echo "### $TOOL_NUM. $test_id" >> "$OUTPUT"
        echo "" >> "$OUTPUT"
        echo "**الأمر**: \`$cmd\`" >> "$OUTPUT"
        echo "" >> "$OUTPUT"
        echo "**السبب**: $reason" >> "$OUTPUT"
        echo "" >> "$OUTPUT"
        echo "---" >> "$OUTPUT"
        echo "" >> "$OUTPUT"
        ((TOOL_NUM++))
    done < "$TEMP_DIR/failed_tools.txt"
else
    echo "**عدد الأدوات التي فشل تشغيلها**: 0" >> "$OUTPUT"
    echo "" >> "$OUTPUT"
    echo "✅ **جميع الأدوات تعمل بشكل صحيح!**" >> "$OUTPUT"
    echo "" >> "$OUTPUT"
fi

# Footer
cat >> "$OUTPUT" << 'FOOTER'
---

## 🎯 الخلاصة

FOOTER

if [ $FAILED -eq 0 ]; then
    echo "✅ **ممتاز!** جميع الاختبارات نجحت بدون أي مشاكل." >> "$OUTPUT"
elif [ $FAILED -le 45 ]; then
    echo "✓ **جيد جداً!** نسبة المشاكل أقل من 10%." >> "$OUTPUT"
elif [ $FAILED -le 90 ]; then
    echo "⚠️ **مقبول** - يوجد بعض المشاكل التي تحتاج إلى مراجعة." >> "$OUTPUT"
else
    echo "❌ **يحتاج إلى تحسين** - عدد كبير من المشاكل." >> "$OUTPUT"
fi

echo "" >> "$OUTPUT"
echo "**تاريخ الانتهاء**: $(date '+%Y-%m-%d %H:%M:%S')" >> "$OUTPUT"

echo "✅ تم حفظ التقرير في: $OUTPUT"
echo ""
echo "================================================================================"
echo "🎉 Task 4 مكتمل!"
echo "================================================================================"

