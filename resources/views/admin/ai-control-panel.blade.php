<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>لوحة تحكم الذكاء الاصطناعي</title>
    <script src="https://cdn.tailwindcss.com" nonce="{{ $cspNonce ?? '' }}"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">🤖 لوحة تحكم الذكاء الاصطناعي</h1>

        <!-- حالة النظام -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">📊 حالة النظام</h2>
            <div id="status" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500 mr-2"></div>
                <span>جاري التحقق...</span>
            </div>
        </div>

        <!-- تحليل النص -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">📝 تحليل النص</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">النص للتحليل:</label>
                    <textarea id="textInput" class="w-full p-3 border border-gray-300 rounded-md" rows="3" placeholder="أدخل النص هنا..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">نوع التحليل:</label>
                    <select id="analysisType" class="w-full p-3 border border-gray-300 rounded-md">
                        <option value="sentiment">تحليل المشاعر</option>
                        <option value="classification">تصنيف المحتوى</option>
                        <option value="keywords">استخراج الكلمات المفتاحية</option>
                    </select>
                </div>
                <button onclick="analyzeText()" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600">
                    تحليل النص
                </button>
                <div id="textResult" class="mt-4 p-4 bg-gray-50 rounded-md hidden">
                    <h3 class="font-semibold mb-2">النتيجة:</h3>
                    <div id="textResultContent"></div>
                </div>
            </div>
        </div>

        <!-- تصنيف المنتجات -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">🏷️ تصنيف المنتجات</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">وصف المنتج:</label>
                    <textarea id="productDescription" class="w-full p-3 border border-gray-300 rounded-md" rows="3" placeholder="أدخل وصف المنتج هنا..."></textarea>
                </div>
                <button onclick="classifyProduct()" class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600">
                    تصنيف المنتج
                </button>
                <div id="productResult" class="mt-4 p-4 bg-gray-50 rounded-md hidden">
                    <h3 class="font-semibold mb-2">التصنيف:</h3>
                    <div id="productResultContent"></div>
                </div>
            </div>
        </div>

        <!-- التوصيات -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">💡 التوصيات</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">تفضيلات المستخدم:</label>
                    <textarea id="userPreferences" class="w-full p-3 border border-gray-300 rounded-md" rows="2" placeholder='{"categories": ["إلكترونيات"], "price_range": [1000, 5000]}'></textarea>
                </div>
                <button onclick="generateRecommendations()" class="bg-purple-500 text-white px-6 py-2 rounded-md hover:bg-purple-600">
                    توليد التوصيات
                </button>
                <div id="recommendationsResult" class="mt-4 p-4 bg-gray-50 rounded-md hidden">
                    <h3 class="font-semibold mb-2">التوصيات:</h3>
                    <div id="recommendationsResultContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        // تحقق من حالة النظام
        async function checkStatus() {
            try {
                const response = await fetch('/admin/ai/status');
                const data = await response.json();
                const statusElement = document.getElementById('status');

                if (data.success) {
                    statusElement.innerHTML = `
                        <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-green-600">النظام يعمل بشكل طبيعي</span>
                    `;
                } else {
                    statusElement.innerHTML = `
                        <div class="w-4 h-4 bg-red-500 rounded-full mr-2"></div>
                        <span class="text-red-600">خطأ في النظام</span>
                    `;
                }
            } catch (error) {
                document.getElementById('status').innerHTML = `
                    <div class="w-4 h-4 bg-red-500 rounded-full mr-2"></div>
                    <span class="text-red-600">خطأ في الاتصال</span>
                `;
            }
        }

        // تحليل النص
        async function analyzeText() {
            const text = document.getElementById('textInput').value;
            const type = document.getElementById('analysisType').value;

            if (!text.trim()) {
                alert('يرجى إدخال نص للتحليل');
                return;
            }

            try {
                const response = await fetch('/admin/ai/analyze-text', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ text, type })
                });

                const data = await response.json();
                showResult('textResult', 'textResultContent', data);
            } catch (error) {
                showError('textResult', 'textResultContent', 'خطأ في تحليل النص');
            }
        }

        // تصنيف المنتج
        async function classifyProduct() {
            const description = document.getElementById('productDescription').value;

            if (!description.trim()) {
                alert('يرجى إدخال وصف المنتج');
                return;
            }

            try {
                const response = await fetch('/admin/ai/classify-product', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ description })
                });

                const data = await response.json();
                showResult('productResult', 'productResultContent', data);
            } catch (error) {
                showError('productResult', 'productResultContent', 'خطأ في تصنيف المنتج');
            }
        }

        // توليد التوصيات
        async function generateRecommendations() {
            const preferencesText = document.getElementById('userPreferences').value;

            if (!preferencesText.trim()) {
                alert('يرجى إدخال تفضيلات المستخدم');
                return;
            }

            try {
                const preferences = JSON.parse(preferencesText);
                const response = await fetch('/admin/ai/recommendations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        preferences,
                        products: []
                    })
                });

                const data = await response.json();
                showResult('recommendationsResult', 'recommendationsResultContent', data);
            } catch (error) {
                showError('recommendationsResult', 'recommendationsResultContent', 'خطأ في توليد التوصيات');
            }
        }

        // عرض النتائج
        function showResult(containerId, contentId, data) {
            const container = document.getElementById(containerId);
            const content = document.getElementById(contentId);

            if (data.success) {
                content.innerHTML = `<pre class="whitespace-pre-wrap">${JSON.stringify(data.data || data, null, 2)}</pre>`;
            } else {
                content.innerHTML = `<div class="text-red-600">خطأ: ${data.message || 'حدث خطأ غير متوقع'}</div>`;
            }

            container.classList.remove('hidden');
        }

        // عرض الأخطاء
        function showError(containerId, contentId, message) {
            const container = document.getElementById(containerId);
            const content = document.getElementById(contentId);

            content.innerHTML = `<div class="text-red-600">${message}</div>`;
            container.classList.remove('hidden');
        }

        // تحقق من الحالة عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', checkStatus);
    </script>
</body>
</html>