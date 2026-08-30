let globalStudentsData = [];
let currentSortCol = 'scanned_at';
let currentSortDir = 'desc';

function normalizeImagePath(path) {
    if (!path) return '';
    path = path.trim();
    if (path.startsWith('data:image/') || path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    if (path.startsWith('uploads/exams/')) {
        return path;
    }
    if (path.startsWith('/uploads/exams/')) {
        return path.substring(1);
    }
    if (path.startsWith('uploads/')) {
        return path;
    }
    return 'uploads/exams/' + path.replace(/^[\/\\]+/, '');
}

function renderStudentTable(students) {
    const studentTbody = document.getElementById('studentTableBody');
    if (!students || students.length === 0) {
        studentTbody.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-slate-400 font-medium">ยังไม่มีข้อมูลนิสิตที่ถูกสแกนในชุดข้อสอบนี้</td></tr>`;
        return;
    }
    
    studentTbody.innerHTML = students.map(s => {
        const normPath = s.image_path ? normalizeImagePath(s.image_path) : '';
        return `
        <tr class="hover:bg-slate-50/80 transition-colors">
            <td class="py-3.5 px-6 font-bold text-slate-900 font-mono text-sm">${escapeHtml(s.student_id)}</td>
            <td class="py-3.5 px-6 text-center"><span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-xs font-bold border border-slate-200">ชุด ${escapeHtml(s.exam_set || 'A')}</span></td>
            <td class="py-3.5 px-6 font-sans"><span class="text-xl font-extrabold text-amber-600 font-mono">${s.score}</span></td>
            <td class="py-3.5 px-6 text-xs text-slate-500">${s.scanned_at ? new Date(s.scanned_at).toLocaleString('th-TH') : '-'}</td>
            <td class="py-3.5 px-6 text-center">
                ${normPath
                    ? `<button type="button" class="bg-white border border-slate-300 hover:bg-slate-50 hover:border-yellow-400 text-slate-700 text-xs font-bold py-1.5 px-3.5 rounded-xl shadow-2xs transition-all view-img-btn active:scale-95 flex items-center gap-1.5 mx-auto" data-img="${escapeHtml(normPath)}">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>ดูภาพ</span>
                       </button>`
                    : '<span class="text-slate-400 text-xs italic">ไม่มีภาพ</span>'}
            </td>
        </tr>
    `}).join('');

    studentTbody.querySelectorAll('.view-img-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const imgSrc = btn.getAttribute('data-img');
            if (imgSrc && typeof window.showImage === 'function') {
                window.showImage(imgSrc);
            }
        });
    });
}

window.sortStudentTable = function(col) {
    if (currentSortCol === col) {
        currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortCol = col;
        currentSortDir = 'asc';
    }

    // Reset indicator icons
    ['student_id', 'exam_set', 'score', 'scanned_at'].forEach(c => {
        const el = document.getElementById(`sort-${c}`);
        if (el) el.textContent = '↕';
    });

    const activeEl = document.getElementById(`sort-${col}`);
    if (activeEl) activeEl.textContent = currentSortDir === 'asc' ? '↑' : '↓';

    globalStudentsData.sort((a, b) => {
        let valA = a[col];
        let valB = b[col];
        if (col === 'score') {
            valA = parseFloat(valA) || 0;
            valB = parseFloat(valB) || 0;
        }
        if (valA < valB) return currentSortDir === 'asc' ? -1 : 1;
        if (valA > valB) return currentSortDir === 'asc' ? 1 : -1;
        return 0;
    });

    renderStudentTable(globalStudentsData);
};

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`api/analytics.php?exam_id=${examId}`);
        const result   = await response.json();

        if (result.status !== 'success') {
            document.getElementById('pageTitle').textContent = 'ไม่สามารถโหลดข้อมูลการสอบได้';
            return;
        }

        const data = result.data;
        document.getElementById('pageTitle').innerHTML = `
            <span>ผลสอบ: ${escapeHtml(data.exam_title)}</span>
            ${data.exam_code ? `<span class="text-sm font-semibold text-slate-500 font-mono">(${escapeHtml(data.exam_code)})</span>` : ''}
        `;

        // ─── Stats Cards ──────────────────────────────────────────────
        document.getElementById('statsGrid').innerHTML = `
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/90 text-center flex flex-col justify-between">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">คะแนนเฉลี่ย (Mean)</div>
                <div class="text-3xl sm:text-4xl font-black text-amber-500 font-sans my-1">${data.summary.avg}</div>
                <div class="text-[11px] text-slate-400 mt-1">คะแนนเฉลี่ยรวมทุกชุด</div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/90 text-center flex flex-col justify-between">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">คะแนนสูงสุด (Max)</div>
                <div class="text-3xl sm:text-4xl font-black text-emerald-600 font-sans my-1">${data.summary.max}</div>
                <div class="text-[11px] text-emerald-700 font-semibold mt-1">สถิติดีที่สุด</div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/90 text-center flex flex-col justify-between">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">คะแนนต่ำสุด (Min)</div>
                <div class="text-3xl sm:text-4xl font-black text-slate-700 font-sans my-1">${data.summary.min}</div>
                <div class="text-[11px] text-slate-400 mt-1">คะแนนต่ำสุดที่ทำได้</div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/90 text-center flex flex-col justify-between">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">ส่วนเบี่ยงเบน (SD)</div>
                <div class="text-3xl sm:text-4xl font-black text-indigo-600 font-sans my-1">${data.summary.std_dev}</div>
                <div class="text-[11px] text-slate-400 mt-1">ค่าเบี่ยงเบนมาตรฐาน</div>
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
                    backgroundColor: '#EAB308',   // MSU Yellow-500
                    hoverBackgroundColor: '#CA8A04', // MSU Yellow-600
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        titleFont: { family: 'Sarabun', size: 13, weight: 'bold' },
                        bodyFont: { family: 'Sarabun', size: 12 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Sarabun', size: 11 }, color: '#64748B' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Sarabun', size: 11 }, color: '#64748B' },
                        grid: { color: '#F1F5F9' }
                    }
                }
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
            let easyCount = 0, hardCount = 0;

            tbody.innerHTML = items.map(item => {
                if (item.quality_flag === 'easy') easyCount++;
                if (item.quality_flag === 'hard') hardCount++;

                const correctAns = item.correct_ans;

                function optionCell(opt) {
                    const d        = item.distribution_pct ? item.distribution_pct[opt] : { count: item.distribution[opt], pct: 0 };
                    const count    = d.count;
                    const pct      = d.pct ?? 0;
                    const isCorrect = correctAns && correctAns.includes(opt);

                    const barColor  = isCorrect ? '#10B981' : '#CBD5E1';
                    const textColor = isCorrect ? 'text-emerald-700 font-extrabold' : 'text-slate-600 font-medium';

                    return `
                        <td class="py-2.5 px-4">
                            <div class="flex flex-col gap-0.5 min-w-[3.5rem]">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="${textColor}">${opt}${isCorrect ? ' ✓' : ''}</span>
                                    <span class="text-slate-400 font-mono">${pct}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full transition-all duration-500"
                                         style="width:${Math.min(pct, 100)}%; background:${barColor}"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 text-right font-mono">${count} คน</span>
                            </div>
                        </td>`;
                }

                const blankD   = item.distribution_pct ? item.distribution_pct['blank'] : { count: 0, pct: 0 };
                const blankCell = `
                    <td class="py-2.5 px-4 text-xs text-slate-400 text-center font-mono">${blankD.count}<br><span class="text-[10px] text-slate-300">(${blankD.pct ?? 0}%)</span></td>`;

                let pClass = 'bg-emerald-50 text-emerald-800 border border-emerald-200';
                if (item.quality_flag === 'easy') pClass = 'bg-yellow-50 text-yellow-800 border border-yellow-300';
                if (item.quality_flag === 'hard') pClass = 'bg-red-50 text-red-700 border border-red-200';

                let statusBadge = '';
                if (item.quality_flag === 'easy') {
                    statusBadge = `<span class="text-xs bg-yellow-100 text-yellow-800 border border-yellow-300 font-bold px-2.5 py-0.5 rounded-full whitespace-nowrap">ง่ายมาก</span>`;
                } else if (item.quality_flag === 'hard') {
                    statusBadge = `<span class="text-xs bg-red-100 text-red-700 border border-red-300 font-bold px-2.5 py-0.5 rounded-full whitespace-nowrap">⚠️ ควรทบทวน</span>`;
                } else if (correctAns !== null) {
                    statusBadge = `<span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2.5 py-0.5 rounded-full whitespace-nowrap">ปกติ</span>`;
                } else {
                    statusBadge = `<span class="text-xs bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-0.5 rounded-full whitespace-nowrap">ยังไม่มีเฉลย</span>`;
                }

                const rowBg = item.quality_flag === 'easy' ? 'bg-yellow-50/30' :
                              item.quality_flag === 'hard' ? 'bg-red-50/30' : '';

                return `
                    <tr class="hover:bg-slate-50 transition-colors ${rowBg}">
                        <td class="py-2.5 px-4 font-bold text-slate-900 font-mono">${item.question}</td>
                        <td class="py-2.5 px-4">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full font-mono ${pClass}">
                                ${item.p_value}
                            </span>
                        </td>
                        ${OPTIONS.map(opt => optionCell(opt)).join('')}
                        ${blankCell}
                        <td class="py-2.5 px-4">${statusBadge}</td>
                    </tr>`;
            }).join('');

            let summaryHtml = '';
            if (easyCount > 0) {
                summaryHtml += `<span class="inline-flex items-center gap-1.5 bg-yellow-50 text-yellow-800 border border-yellow-300 text-xs font-bold px-3 py-1 rounded-full">
                    ⚡ ข้อง่ายมาก: <strong>${easyCount} ข้อ</strong> (P &gt; 0.80)
                </span>`;
            }
            if (hardCount > 0) {
                summaryHtml += `<span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 border border-red-300 text-xs font-bold px-3 py-1 rounded-full">
                    ⚠️ ข้อที่ควรทบทวนโจทย์: <strong>${hardCount} ข้อ</strong> (P &lt; 0.20)
                </span>`;
            }
            if (easyCount === 0 && hardCount === 0 && items.length > 0) {
                summaryHtml = `<span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold px-3 py-1 rounded-full">
                    ✅ ข้อสอบทุกข้อมีความยากง่ายอยู่ในเกณฑ์ที่เหมาะสม
                </span>`;
            }
            qualDiv.innerHTML = summaryHtml;
        }

        // ─── Student Table Render & Data init ─────────────────────────
        globalStudentsData = data.students || [];
        renderStudentTable(globalStudentsData);

    } catch (error) {
        console.error('Failed to fetch analytics:', error);
        document.getElementById('pageTitle').textContent = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์';
    }
});
