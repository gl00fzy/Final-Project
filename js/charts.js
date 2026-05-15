document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`api/analytics.php?exam_id=${examId}`);
        const result = await response.json();

        if (result.status !== 'success') {
            alert('Error loading data: ' + result.message);
            return;
        }

        const data = result.data;
        document.getElementById('pageTitle').textContent = `ผลสอบ: ${data.exam_title}`;

        // Populate Stats
        document.getElementById('statsGrid').innerHTML = `
            <div class="stat-card">
                <div class="text-gray-500 text-sm font-medium mb-1">คะแนนเฉลี่ย</div>
                <div class="stat-value">${data.summary.avg}</div>
            </div>
            <div class="stat-card">
                <div class="text-gray-500 text-sm font-medium mb-1">คะแนนสูงสุด</div>
                <div class="stat-value">${data.summary.max}</div>
            </div>
            <div class="stat-card">
                <div class="text-gray-500 text-sm font-medium mb-1">คะแนนต่ำสุด</div>
                <div class="stat-value">${data.summary.min}</div>
            </div>
            <div class="stat-card">
                <div class="text-gray-500 text-sm font-medium mb-1">ส่วนเบี่ยงเบน (SD)</div>
                <div class="stat-value">${data.summary.std_dev}</div>
            </div>
        `;

        // Render Histogram
        const ctx = document.getElementById('histogramChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.histogram.labels,
                datasets: [{
                    label: 'จำนวนนิสิต',
                    data: data.histogram.data,
                    backgroundColor: '#10B981',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // Render Item Analysis
        const itemGrid = document.getElementById('itemAnalysisGrid');
        if (data.item_analysis.length === 0) {
            itemGrid.innerHTML = '<p class="text-gray-500">ยังไม่มีข้อมูลการฝนคำตอบ</p>';
        } else {
            itemGrid.innerHTML = data.item_analysis.map(item => `
                <div class="item-card ${item.is_hard ? 'hard' : ''}">
                    <div class="flex justify-between items-center mb-2">
                        <strong class="text-gray-900">ข้อ ${item.question}</strong>
                        <span class="${item.is_hard ? 'text-red-600' : 'text-emerald-600'} font-bold">
                            ตอบถูก ${item.correct_pct}%
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 font-mono tracking-tight bg-gray-50 px-2 py-1 rounded-md border border-gray-100">
                        <span class="mr-2">A:${item.distribution.A}</span>
                        <span class="mr-2">B:${item.distribution.B}</span>
                        <span class="mr-2">C:${item.distribution.C}</span>
                        <span class="mr-2">D:${item.distribution.D}</span>
                        <span>E:${item.distribution.E}</span>
                    </div>
                </div>
            `).join('');
        }

        // Render Students Table
        const tbody = document.getElementById('studentTableBody');
        tbody.innerHTML = data.students.map(s => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="py-3 px-6 font-semibold text-gray-900">${s.student_id}</td>
                <td class="py-3 px-6"><span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-bold border border-gray-200">${s.exam_set || 'A'}</span></td>
                <td class="py-3 px-6"><span class="text-xl font-black text-emerald-600">${s.score}</span></td>
                <td class="py-3 px-6 text-sm text-gray-500">${new Date(s.scanned_at).toLocaleString('th-TH')}</td>
                <td class="py-3 px-6 text-center">
                    ${s.image_path ? 
                        `<button class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium py-1.5 px-4 rounded-lg shadow-sm transition-colors" onclick="window.showImage('${s.image_path}')">ดูภาพ</button>` 
                        : '<span class="text-gray-400 text-sm italic">ไม่มีภาพ</span>'}
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error("Failed to fetch analytics:", error);
        alert('Cannot connect to server.');
    }
});

