#!/bin/bash

################################################################################
# تنفيذ جميع الـ 450 اختبار/أداة - عرض مباشر في التيرمنال
################################################################################

set +e  # Don't exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Files
INVENTORY="FRESH_COMPREHENSIVE_TESTS_AND_TOOLS_INVENTORY_2025.md"
OUTPUT="TASK_4_NEGATIVE_OUTPUTS_ONLY.md"
TEMP_DIR="temp_task4_visible"

# Counters
TOTAL=450
PASSED=0
FAILED=0

# Create temp directory
mkdir -p "$TEMP_DIR"

# Start time
START_TIME=$(date +%s)

echo -e "${BLUE}================================================================================${NC}"
echo -e "${BLUE}🚀 Task 4 - تنفيذ جميع الـ 450 اختبار/أداة${NC}"
echo -e "${BLUE}مشروع COPRRA${NC}"
echo -e "${BLUE}================================================================================${NC}"
echo ""
echo -e "${CYAN}📖 قراءة الأوامر من الملف...${NC}"

# Extract commands
grep -oP '(?<=\*\*الأمر\*\*: `).*(?=`)' "$INVENTORY" > "$TEMP_DIR/commands.txt"

CMD_COUNT=$(wc -l < "$TEMP_DIR/commands.txt")
echo -e "${GREEN}✅ تم استخراج $CMD_COUNT أمر${NC}"
echo ""
echo -e "${YELLOW}🚀 بدء التنفيذ...${NC}"
echo -e "${BLUE}================================================================================${NC}"
echo ""

# Read commands and execute
COUNTER=1
while IFS= read -r cmd; do
    # Display test info
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${MAGENTA}[${COUNTER}/${TOTAL}]${NC} ${YELLOW}تنفيذ:${NC} ${cmd}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    # Execute command
    OUTPUT_FILE="$TEMP_DIR/test_${COUNTER}_output.txt"
    EXIT_CODE=0
    
    echo -e "${BLUE}⏳ جاري التنفيذ...${NC}"
    echo ""
    
    # Execute and show output in real-time
    timeout 300 bash -c "$cmd" 2>&1 | tee "$OUTPUT_FILE"
    EXIT_CODE=${PIPESTATUS[0]}
    
    echo ""
    
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
    
    # Display result
    if [ $HAS_ERROR -eq 1 ]; then
        echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${RED}❌ النتيجة: فشل (Exit Code: $EXIT_CODE)${NC}"
        echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        ((FAILED++))
        # Store negative output
        echo "TEST_${COUNTER}|${cmd}|${EXIT_CODE}|${OUTPUT_FILE}" >> "$TEMP_DIR/negative_outputs.txt"
    else
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${GREEN}✅ النتيجة: نجح${NC}"
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        ((PASSED++))
        # Remove output file for passed tests
        rm -f "$OUTPUT_FILE"
    fi
    
    echo ""
    
    # Progress summary
    PERCENTAGE=$((COUNTER * 100 / TOTAL))
    echo -e "${BLUE}📊 التقدم الإجمالي: ${COUNTER}/${TOTAL} (${PERCENTAGE}%) | ${GREEN}✅ ${PASSED}${NC} | ${RED}❌ ${FAILED}${NC}"
    
    # Time estimation
    if [ $COUNTER -ge 5 ]; then
        ELAPSED=$(($(date +%s) - START_TIME))
        AVG_TIME=$((ELAPSED / COUNTER))
        REMAINING=$(( (TOTAL - COUNTER) * AVG_TIME ))
        echo -e "${YELLOW}⏱️  الوقت المتبقي المتوقع: $((REMAINING / 60)) دقيقة و $((REMAINING % 60)) ثانية${NC}"
    fi
    
    echo ""
    echo ""
    
    ((COUNTER++))
done < "$TEMP_DIR/commands.txt"

# End time
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo ""
echo -e "${BLUE}================================================================================${NC}"
echo -e "${GREEN}✅ اكتمل التنفيذ!${NC}"
echo -e "${BLUE}================================================================================${NC}"
echo ""
echo -e "${CYAN}📊 النتائج النهائية:${NC}"
echo -e "  ${BLUE}▪${NC} إجمالي الاختبارات: ${MAGENTA}${TOTAL}${NC}"
echo -e "  ${GREEN}▪ نجح: ${PASSED}${NC}"
echo -e "  ${RED}▪ فشل: ${FAILED}${NC}"
echo -e "  ${YELLOW}▪ نسبة النجاح: $((PASSED * 100 / TOTAL))%${NC}"
echo -e "  ${CYAN}▪ المدة: $((DURATION / 60)) دقيقة و $((DURATION % 60)) ثانية${NC}"
echo ""

# Generate report
echo -e "${YELLOW}💾 إنشاء التقرير النهائي...${NC}"

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

echo -e "${GREEN}✅ تم حفظ التقرير في: ${OUTPUT}${NC}"
echo ""
echo -e "${BLUE}================================================================================${NC}"
echo -e "${GREEN}🎉 Task 4 مكتمل!${NC}"
echo -e "${BLUE}================================================================================${NC}"

