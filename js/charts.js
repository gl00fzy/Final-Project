document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`api/analytics.php?exam_id=${examId}`);
        const result   = await response.json();

        if (result.status !== 'success') {
            document.getElementById('pageTitle').textContent = 'ไม่สามารถโหลดข้อมูลได้';
            return;
        }

        const data = result.data;
        document.getElementById('pageTitle').textContent = `ผลสอบ: ${data.exam_title}`;

        // ─── Stats Cards ──────────────────────────────────────────────
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

        // ─── Histogram ────────────────────────────────────────────────
        const ctx = document.getElementById('histogramChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.histogram.labels,
                datasets: [{
                    label: 'จำนวนนิสิต',
                    data: data.histogram.data,
                    backgroundColor: '#EAB308',   // yellow-500
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // ─── Item Analysis Table ──────────────────────────────────────
        const items    = data.item_analysis;
        const tbody    = document.getElementById('itemAnalysisBody');
        const emptyDiv = document.getElementById('itemAnalysisEmpty');
        const qualDiv  = document.getElementById('qualitySummary');

        const OPTIONS  = ['A', 'B', 'C', 'D', 'E'];

        if (!items || items.length === 0) {
            document.getElementById('itemAnalysisTable').classList.add('hidden');
            emptyDiv.classList.remove('hidden');
        } else {
            // Count quality flags for summary
            let easyCount = 0, hardCount = 0;

            tbody.innerHTML = items.map(item => {
                if (item.quality_flag === 'easy') easyCount++;
                if (item.quality_flag === 'hard') hardCount++;

                const correctAns = item.correct_ans; // e.g. "A" or "B+C"

                // Build bar for each option
                function optionCell(opt) {
                    const d        = item.distribution_pct ? item.distribution_pct[opt] : { count: item.distribution[opt], pct: 0 };
                    const count    = d.count;
                    const pct      = d.pct ?? 0;
                    const isCorrect = correctAns && correctAns.includes(opt);

                    const barColor  = isCorrect ? '#22C55E' : '#E5E7EB'; // green-500 : gray-200
                    const textColor = isCorrect ? 'text-green-700 font-bold' : 'text-gray-600';

                    return `
                        <td class="py-2 px-4">
                            <div class="flex flex-col gap-0.5 min-w-[3.5rem]">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs ${textColor}">${opt}${isCorrect ? ' ✓' : ''}</span>
                                    <span class="text-xs text-gray-400">${pct}%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                         style="width:${Math.min(pct, 100)}%; background:${barColor}"></div>
                                </div>
                                <span class="text-xs text-gray-400 text-right">${count}</span>
                            </div>
                        </td>`;
                }

                // Blank cell
                const blankD   = item.distribution_pct ? item.distribution_pct['blank'] : { count: 0, pct: 0 };
                const blankCell = `
                    <td class="py-2 px-4 text-xs text-gray-400 text-center">${blankD.count}<br><span class="text-gray-300">(${blankD.pct ?? 0}%)</span></td>`;

                // P-value pill color
                let pClass = 'bg-green-50 text-green-700 border border-green-200';
                if (item.quality_flag === 'easy') pClass = 'bg-yellow-50 text-yellow-700 border border-yellow-300';
                if (item.quality_flag === 'hard') pClass = 'bg-red-50 text-red-600 border border-red-200';

                // Status badge
                let statusBadge = '';
                if (item.quality_flag === 'easy') {
                    statusBadge = `<span class="text-xs bg-yellow-100 text-yellow-800 border border-yellow-300 font-semibold px-2 py-0.5 rounded-full whitespace-nowrap">ง่ายมาก</span>`;
                } else if (item.quality_flag === 'hard') {
                    statusBadge = `<span class="text-xs bg-red-100 text-red-700 border border-red-300 font-semibold px-2 py-0.5 rounded-full whitespace-nowrap">⚠ ควรทบทวน</span>`;
                } else if (correctAns !== null) {
                    statusBadge = `<span class="text-xs bg-green-50 text-green-700 border border-green-200 font-semibold px-2 py-0.5 rounded-full whitespace-nowrap">ปกติ</span>`;
                } else {
                    statusBadge = `<span class="text-xs bg-gray-100 text-gray-400 border border-gray-200 px-2 py-0.5 rounded-full whitespace-nowrap">ยังไม่มีเฉลย</span>`;
                }

                const rowBg = item.quality_flag === 'easy' ? 'bg-yellow-50/40' :
                              item.quality_flag === 'hard' ? 'bg-red-50/40' : '';

                return `
                    <tr class="hover:bg-gray-50 transition-colors ${rowBg}">
                        <td class="py-2 px-4 font-bold text-gray-900">${item.question}</td>
                        <td class="py-2 px-4">
                            <span class="text-xs font-bold px-2 py-1 rounded-full ${pClass}">
                                ${item.p_value}
                            </span>
                        </td>
                        ${OPTIONS.map(opt => optionCell(opt)).join('')}
                        ${blankCell}
                        <td class="py-2 px-4">${statusBadge}</td>
                    </tr>`;
            }).join('');

            // Quality Summary Badges
            let summaryHtml = '';
            if (easyCount > 0) {
                summaryHtml += `<span class="inline-flex items-center gap-1.5 bg-yellow-100 text-yellow-800 border border-yellow-300 text-sm font-semibold px-3 py-1.5 rounded-full">
                    ⚡ ง่ายมาก: <strong>${easyCount} ข้อ</strong> (P &gt; 0.80)
                </span>`;
            }
            if (hardCount > 0) {
                summaryHtml += `<span class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 border border-red-300 text-sm font-semibold px-3 py-1.5 rounded-full">
                    ⚠️ ควรทบทวนโจทย์: <strong>${hardCount} ข้อ</strong> (P &lt; 0.20)
                </span>`;
            }
            if (easyCount === 0 && hardCount === 0 && items.length > 0) {
                summaryHtml = `<span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 border border-green-200 text-sm font-semibold px-3 py-1.5 rounded-full">
                    ✅ ความยากข้อสอบอยู่ในเกณฑ์ที่เหมาะสมทุกข้อ
                </span>`;
            }
            qualDiv.innerHTML = summaryHtml;
        }

        // ─── Student Table ────────────────────────────────────────────
        const studentTbody = document.getElementById('studentTableBody');
        if (data.students.length === 0) {
            studentTbody.innerHTML = `<tr><td colspan="5" class="py-10 text-center text-gray-400">ยังไม่มีนิสิตที่ถูกสแกน</td></tr>`;
        } else {
            studentTbody.innerHTML = data.students.map(s => `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-6 font-semibold text-gray-900">${s.student_id}</td>
                    <td class="py-3 px-6"><span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-bold border border-gray-200">${s.exam_set || 'A'}</span></td>
                    <td class="py-3 px-6"><span class="text-xl font-black text-yellow-600">${s.score}</span></td>
                    <td class="py-3 px-6 text-sm text-gray-500">${new Date(s.scanned_at).toLocaleString('th-TH')}</td>
                    <td class="py-3 px-6 text-center">
                        ${s.image_path
                            ? `<button class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium py-1.5 px-4 rounded-lg shadow-sm transition-colors" onclick="window.showImage('${s.image_path}')">ดูภาพ</button>`
                            : '<span class="text-gray-400 text-sm italic">ไม่มีภาพ</span>'}
                    </td>
                </tr>
            `).join('');
        }

    } catch (error) {
        console.error('Failed to fetch analytics:', error);
        document.getElementById('pageTitle').textContent = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์';
    }
});
