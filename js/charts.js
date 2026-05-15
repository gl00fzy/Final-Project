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
                <div class="text-muted">คะแนนเฉลี่ย</div>
                <div class="stat-value">${data.summary.avg}</div>
            </div>
            <div class="stat-card">
                <div class="text-muted">คะแนนสูงสุด</div>
                <div class="stat-value">${data.summary.max}</div>
            </div>
            <div class="stat-card">
                <div class="text-muted">คะแนนต่ำสุด</div>
                <div class="stat-value">${data.summary.min}</div>
            </div>
            <div class="stat-card">
                <div class="text-muted">ส่วนเบี่ยงเบน (SD)</div>
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
            itemGrid.innerHTML = '<p>ยังไม่มีข้อมูลการฝนคำตอบ</p>';
        } else {
            itemGrid.innerHTML = data.item_analysis.map(item => `
                <div class="item-card ${item.is_hard ? 'hard' : ''}">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <strong>ข้อ ${item.question}</strong>
                        <span style="color: ${item.is_hard ? 'var(--error-color)' : 'var(--success-color)'}; font-weight: bold;">
                            ตอบถูก ${item.correct_pct}%
                        </span>
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-muted);">
                        A: ${item.distribution.A} | B: ${item.distribution.B} | C: ${item.distribution.C} | D: ${item.distribution.D} | E: ${item.distribution.E}
                    </div>
                </div>
            `).join('');
        }

        // Render Students Table
        const tbody = document.getElementById('studentTableBody');
        tbody.innerHTML = data.students.map(s => `
            <tr>
                <td><strong>${s.student_id}</strong></td>
                <td><span style="background: var(--bg-color); padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 600;">${s.exam_set || 'A'}</span></td>
                <td><span class="stat-value" style="font-size: 1.25rem;">${s.score}</span></td>
                <td class="text-muted">${new Date(s.scanned_at).toLocaleString('th-TH')}</td>
                <td>
                    ${s.image_path ? 
                        `<button class="btn btn-outline" style="padding: 0.5rem; font-size: 0.875rem;" onclick="viewImage('${s.image_path}')">ดูภาพ</button>` 
                        : '<span class="text-muted">ไม่มีภาพ</span>'}
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error("Failed to fetch analytics:", error);
        alert('Cannot connect to server.');
    }
});

// Image Modal Logic
const imageModal = document.getElementById('imageModal');
const scannedImage = document.getElementById('scannedImage');
const closeImageBtn = document.getElementById('closeImageBtn');

function viewImage(path) {
    scannedImage.src = path;
    imageModal.style.display = 'flex';
}

closeImageBtn.addEventListener('click', () => {
    imageModal.style.display = 'none';
    scannedImage.src = '';
});
