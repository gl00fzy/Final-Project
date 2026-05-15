let video = document.getElementById('video');
let canvasOutput = document.getElementById('canvasOutput');
let ctx = canvasOutput.getContext('2d');
let debugCanvas = document.getElementById('debug-canvas');
let videoWrapper = document.getElementById('video-wrapper');
let statusIndicator = document.getElementById('statusIndicator');

let streaming = false;
let src = null;
let dst = null;
let gray = null;
let cap = null;

// Audio context for beep sound
const beepSound = new Audio('data:audio/mp3;base64,//NExAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq'); // Fallback silent/placeholder beep if file not found
beepSound.volume = 0.5;

function playBeep() {
    try {
        beepSound.play().catch(e => console.log('Audio play error', e));
    } catch(e) {}
}

function onOpenCvReady() {
    statusIndicator.textContent = "กำลังเปิดกล้อง กรุณารอสักครู่...";
    cv['onRuntimeInitialized'] = () => {
        startCamera();
    };
}

function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        statusIndicator.textContent = "เบราว์เซอร์ไม่รองรับการเปิดกล้อง (ตรวจสอบ HTTPS)";
        videoWrapper.classList.add('error');
        return;
    }

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
        .then(function(stream) {
            video.srcObject = stream;
            video.play();
            streaming = true;
        })
        .catch(function(err) {
            console.log("An error occurred! " + err);
            statusIndicator.textContent = "ไม่สามารถเปิดกล้องได้";
            videoWrapper.classList.add('error');
        });

    video.addEventListener('canplay', function(ev){
        if (streaming) {
            canvasOutput.width = video.videoWidth;
            canvasOutput.height = video.videoHeight;
            src = new cv.Mat(video.videoHeight, video.videoWidth, cv.CV_8UC4);
            dst = new cv.Mat(video.videoHeight, video.videoWidth, cv.CV_8UC1);
            gray = new cv.Mat();
            cap = new cv.VideoCapture(video);
            
            statusIndicator.textContent = "เล็งกล้องให้เห็นสี่เหลี่ยมครบ 4 มุม...";
            requestAnimationFrame(processVideo);
        }
    }, false);
}

function processVideo() {
    if (!streaming) return;
    
    try {
        cap.read(src);
        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
        cv.GaussianBlur(gray, dst, new cv.Size(5, 5), 0, 0, cv.BORDER_DEFAULT);
        cv.Canny(dst, dst, 50, 150, 3, false);

        let contours = new cv.MatVector();
        let hierarchy = new cv.Mat();
        cv.findContours(dst, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

        let markers = [];
        
        // Find square contours (markers)
        for (let i = 0; i < contours.size(); ++i) {
            let cnt = contours.get(i);
            let area = cv.contourArea(cnt);
            
            // Filter by area to avoid noise
            if (area > 500 && area < 10000) {
                let perimeter = cv.arcLength(cnt, true);
                let approx = new cv.Mat();
                cv.approxPolyDP(cnt, approx, 0.04 * perimeter, true);
                
                if (approx.rows === 4) {
                    // It's a quad, calculate aspect ratio to ensure it's somewhat square
                    let rect = cv.boundingRect(approx);
                    let aspect_ratio = rect.width / rect.height;
                    
                    if (aspect_ratio >= 0.8 && aspect_ratio <= 1.2) {
                        // Find center point
                        let M = cv.moments(cnt);
                        let cx = M.m10 / M.m00;
                        let cy = M.m01 / M.m00;
                        markers.push({ x: cx, y: cy, rect: rect });
                    }
                }
                approx.delete();
            }
        }

        // Draw results on canvasOutput
        ctx.clearRect(0, 0, canvasOutput.width, canvasOutput.height);
        
        if (markers.length === 4) {
            videoWrapper.classList.remove('error');
            videoWrapper.classList.add('success');
            statusIndicator.textContent = "เจอครบ 4 มุมแล้ว - กรุณาถือกล้องนิ่งๆ...";
            
            // Draw green dots on markers
            ctx.fillStyle = '#10B981';
            markers.forEach(m => {
                ctx.beginPath();
                ctx.arc(m.x, m.y, 8, 0, 2 * Math.PI);
                ctx.fill();
            });

            // Sort points: TL, TR, BR, BL
            // 1. Sort by Y-coordinate
            markers.sort((a, b) => a.y - b.y);
            // Top two points
            let topPoints = markers.slice(0, 2);
            // Bottom two points
            let bottomPoints = markers.slice(2, 4);
            
            // 2. Sort by X-coordinate to get Left/Right
            topPoints.sort((a, b) => a.x - b.x);
            bottomPoints.sort((a, b) => a.x - b.x);
            
            let tl = topPoints[0], tr = topPoints[1];
            let bl = bottomPoints[0], br = bottomPoints[1];

            // Perspective Transform
            // Target dimensions for a typical A4 proportion (e.g., 600x848)
            let width = 600;
            let height = 848;
            
            let srcTri = null, dstTri = null, M = null, warped = null, warpedGray = null, binary = null;

            try {
                srcTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
                    tl.x, tl.y, tr.x, tr.y, br.x, br.y, bl.x, bl.y
                ]);
                dstTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
                    0, 0, width, 0, width, height, 0, height
                ]);

                M = cv.getPerspectiveTransform(srcTri, dstTri);
                warped = new cv.Mat();
                cv.warpPerspective(src, warped, M, new cv.Size(width, height), cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar());
                
                // --- Basic Bubble Detection ---
                // 1. Convert warped image to grayscale & binary inverse
                warpedGray = new cv.Mat();
                cv.cvtColor(warped, warpedGray, cv.COLOR_RGBA2GRAY);
                binary = new cv.Mat();
                // Inverse threshold so filled bubbles (black ink) become white pixels
                cv.threshold(warpedGray, binary, 100, 255, cv.THRESH_BINARY_INV | cv.THRESH_OTSU);

                // For PoC, let's just define a bounding box where Question 1 (A-E) might be.
                // Say it starts at x=100, y=200, bubble width=30, height=30, gap=15.
                let startX = 100, startY = 200, bSize = 30, gap = 15;
                let options = ['A', 'B', 'C', 'D', 'E'];
                let maxPixels = 0;
                let selectedOption = null;

                for (let i = 0; i < 5; i++) {
                    let bx = startX + i * (bSize + gap);
                    let by = startY;
                    
                    // Ensure ROI is within bounds
                    if (bx + bSize < width && by + bSize < height) {
                        let rect = new cv.Rect(bx, by, bSize, bSize);
                        let roi = binary.roi(rect);
                        let filledPixels = cv.countNonZero(roi);
                        
                        // Draw a blue box on the color warped image to visualize
                        let point1 = new cv.Point(bx, by);
                        let point2 = new cv.Point(bx + bSize, by + bSize);
                        let color = new cv.Scalar(255, 0, 0, 255); // Blue
                        cv.rectangle(warped, point1, point2, color, 2);

                        if (filledPixels > maxPixels && filledPixels > (bSize * bSize * 0.4)) { // 40% filled
                            maxPixels = filledPixels;
                            selectedOption = options[i];
                        }
                        roi.delete();
                    }
                }

                if (selectedOption) {
                    // Draw text showing detected option
                    cv.putText(warped, "Q1: " + selectedOption, new cv.Point(startX, startY - 10), cv.FONT_HERSHEY_SIMPLEX, 1, new cv.Scalar(0, 255, 0, 255), 2);
                }

                // === MOCK PHASE 2 LOGIC ===
                // Generate mock raw answers for Item Analysis
                let mockAnswers = {};
                for(let q=1; q<=50; q++) {
                    // Skew distribution so it's not totally uniform
                    let r = Math.random();
                    if(r < 0.4) mockAnswers[q] = 'A';
                    else if(r < 0.7) mockAnswers[q] = 'B';
                    else if(r < 0.85) mockAnswers[q] = 'C';
                    else if(r < 0.95) mockAnswers[q] = 'D';
                    else mockAnswers[q] = 'E';
                }
                
                // Draw mock overlay circles on warped mat
                for(let q=1; q<=50; q++) {
                    let rx = 50 + ((q-1)%5)*100;
                    let ry = 50 + Math.floor((q-1)/5)*70;
                    let isCorrect = Math.random() > 0.3; // 70% correct rate
                    let color = isCorrect ? new cv.Scalar(0, 255, 0, 255) : new cv.Scalar(255, 0, 0, 255);
                    cv.circle(warped, new cv.Point(rx, ry), 15, color, 3);
                }

                // Display warped image to debug canvas (which is hidden/small by default but can be made visible for debugging)
                debugCanvas.style.display = 'block';
                cv.imshow('debug-canvas', warped);
                
                // Capture the image as JPEG base64
                let base64Image = debugCanvas.toDataURL('image/jpeg', 0.7);
                
                // Simulate successful scan extraction
                if (!isSubmitting) {
                     if (scanMode === 'key') {
                         submitKey(JSON.stringify(mockAnswers));
                     } else {
                         // Example dummy student ID for PoC. In production, this comes from OCR.
                         submitScore('64010000001', 45, JSON.stringify(mockAnswers), base64Image); 
                     }
                }
            } finally {
                if (srcTri) srcTri.delete();
                if (dstTri) dstTri.delete();
                if (M) M.delete();
                if (warped) warped.delete();
                if (warpedGray) warpedGray.delete();
                if (binary) binary.delete();
            }
        } else {
            videoWrapper.classList.add('error');
            videoWrapper.classList.remove('success');
            statusIndicator.textContent = `เห็นสี่เหลี่ยมดำ ${markers.length} จาก 4 มุม (ขยับกล้องอีกนิดครับ)`;
            debugCanvas.style.display = 'none';
            
            // Draw red dots on found markers
            ctx.fillStyle = '#EF4444';
            markers.forEach(m => {
                ctx.beginPath();
                ctx.arc(m.x, m.y, 8, 0, 2 * Math.PI);
                ctx.fill();
            });
        }

        contours.delete(); hierarchy.delete();
        
        requestAnimationFrame(processVideo);
    } catch (err) {
        console.error("OpenCV Processing Error:", err);
    }
}

let scannedStudentIds = new Set();
let isSubmitting = false;
let examId = document.getElementById('examId')?.value || document.querySelector('input[name="exam_id"]')?.value || 1;
let scanMode = 'student'; // 'student' | 'key'

// UI Mode Switcher
window.setScanMode = function(mode) {
    scanMode = mode;
    const btnStudent = document.getElementById('modeStudentBtn');
    const btnKey = document.getElementById('modeKeyBtn');
    
    if (mode === 'student') {
        btnStudent.className = "px-4 py-2 rounded-lg text-sm font-bold transition-colors bg-white text-gray-900 shadow-sm";
        btnKey.className = "px-4 py-2 rounded-lg text-sm font-bold transition-colors text-gray-900 hover:bg-black/10";
        statusIndicator.textContent = 'โหมดตรวจกระดาษคำตอบ';
        statusIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
    } else {
        btnKey.className = "px-4 py-2 rounded-lg text-sm font-bold transition-colors bg-blue-600 text-white shadow-sm";
        btnStudent.className = "px-4 py-2 rounded-lg text-sm font-bold transition-colors text-gray-900 hover:bg-black/10";
        statusIndicator.textContent = 'โหมดสร้างเฉลย (Scan as Key)';
        statusIndicator.style.backgroundColor = 'rgba(37, 99, 235, 0.9)'; // Blue
    }
};

async function submitKey(rawAnswers) {
    if (isSubmitting) return;
    isSubmitting = true;
    
    statusIndicator.textContent = 'กำลังบันทึกเฉลย...';
    statusIndicator.style.backgroundColor = 'rgba(37, 99, 235, 0.9)'; // Blue
    let examSet = document.getElementById('examSetScanner')?.value || 'A';

    const formData = new FormData();
    formData.append('exam_id', examId);
    formData.append('exam_set', examSet);
    formData.append('raw_answers', rawAnswers);

    try {
        const response = await fetch('api/scan_key.php', { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.status === 'success') {
            playBeep();
            statusIndicator.textContent = `✅ บันทึกเฉลยชุด ${examSet} และตรวจใหม่ ${data.regraded_count} คน`;
            statusIndicator.style.backgroundColor = 'rgba(16, 185, 129, 0.9)'; // Green
            videoWrapper.style.borderColor = '#2563EB'; // Blue border for Key Mode success
        } else {
            statusIndicator.textContent = '⚠️ Error: ' + data.message;
            statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)'; // Red
        }
    } catch (error) {
        statusIndicator.textContent = '⚠️ ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
        statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)';
    }

    setTimeout(() => {
        isSubmitting = false;
        setScanMode('key'); // Reset UI to key mode visuals
        videoWrapper.style.borderColor = 'var(--border-color)';
    }, 4000);
}

async function submitScore(studentId, score, rawAnswers = '{}', imageBase64 = '') {
    if (isSubmitting) return;
    if (scannedStudentIds.has(studentId)) {
        statusIndicator.textContent = '❌ รหัสนิสิตนี้ตรวจไปแล้วครับ (สแกนซ้ำ)';
        statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)'; // Red
        return;
    }

    isSubmitting = true;
    statusIndicator.textContent = 'กำลังบันทึกคะแนน...';
    
    let examSet = document.getElementById('examSetScanner')?.value || 'A';

    const formData = new FormData();
    formData.append('exam_id', examId);
    formData.append('student_id', studentId);
    formData.append('score', score); // Ignored by backend now
    formData.append('exam_set', examSet);
    formData.append('raw_answers', rawAnswers);
    formData.append('image', imageBase64);

    try {
        const response = await fetch('api/scores.php', { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.status === 'success') {
            scannedStudentIds.add(studentId);
            playBeep();
            statusIndicator.textContent = '✅ บันทึกคะแนนสำเร็จ';
            statusIndicator.style.backgroundColor = 'rgba(16, 185, 129, 0.9)'; // Green
            
            // Show big overlay
            videoWrapper.style.borderColor = 'var(--success-color)';
            
            const resultCard = document.getElementById('scanResultCard');
            if(resultCard) {
                let studentName = typeof studentDirectory !== 'undefined' && studentDirectory[studentId] ? studentDirectory[studentId] : 'ไม่มีชื่อในระบบ';
                
                function escapeHtml(text) {
                    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
                }

                document.getElementById('resStudentId').innerHTML = `${escapeHtml(studentId)}<br><span style="font-size: 1.5rem; color: #4B5563;">${escapeHtml(studentName)}</span>`;
                document.getElementById('resScore').textContent = data.calculated_score !== undefined ? data.calculated_score : score;
                resultCard.classList.remove('hidden');
            }
        } else if (data.status === 'duplicate') {
            scannedStudentIds.add(studentId); // Add to local set to prevent further hits
            statusIndicator.textContent = '❌ ' + data.message;
            statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)'; // Red
        } else {
            statusIndicator.textContent = '⚠️ Error: ' + data.message;
        }
    } catch (error) {
        statusIndicator.textContent = '⚠️ ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
    }

    // Reset after 3 seconds
    setTimeout(() => {
        isSubmitting = false;
        statusIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
        statusIndicator.textContent = 'เล็งกล้องให้เห็นสี่เหลี่ยมครบ 4 มุม...';
        videoWrapper.style.borderColor = 'var(--border-color)';
        const resultCard = document.getElementById('scanResultCard');
        if(resultCard) resultCard.classList.add('hidden');
    }, 3000);
}

// Manual Entry Logic
const manualModal = document.getElementById('manualModal');
const btnManual = document.getElementById('btnManual');
const btnCancelManual = document.getElementById('btnCancelManual');
const manualForm = document.getElementById('manualForm');

btnManual.addEventListener('click', () => {
    manualModal.classList.remove('hidden');
    manualModal.classList.add('flex');
});
btnCancelManual.addEventListener('click', () => {
    manualModal.classList.remove('flex');
    manualModal.classList.add('hidden');
});

manualForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const studentId = document.getElementById('studentId').value;
    const score = document.getElementById('score').value;
    
    manualModal.classList.remove('flex');
    manualModal.classList.add('hidden');
    await submitScore(studentId, score);
    manualForm.reset();
});
