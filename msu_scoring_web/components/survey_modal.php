<?php
/**
 * MSU Scoring - Survey & Evaluation Modal Component
 * Google Form: แบบประเมินประสิทธิภาพและการยอมรับระบบตรวจข้อสอบปรนัยอัจฉริยะ (MSU Score)
 */
$survey_form_url = "https://docs.google.com/forms/d/e/1FAIpQLScadKdz_wPk-QtWbxFKyINhTf2TKhS__R-QgMEKsurSZ2Nitg/viewform?usp=dialog";
?>
<!-- ════ SURVEY & EVALUATION MODAL DIALOG ═════════════════════════════ -->
<dialog id="surveyModal" aria-labelledby="surveyModalTitle" class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl w-full max-w-lg p-6 sm:p-7 border border-slate-200/80 backdrop:bg-slate-900/60 backdrop:backdrop-blur-sm m-auto overflow-hidden relative">
    <!-- Top Accent Bar (MSU Yellow / Gold Gradient) -->
    <div class="h-1.5 w-full bg-gradient-to-r from-yellow-400 via-amber-500 to-yellow-500 absolute top-0 left-0"></div>
    
    <!-- Modal Header -->
    <div class="flex items-start justify-between gap-3 mb-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 text-slate-950 flex items-center justify-center flex-shrink-0 shadow-sm mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <div>
                <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md mb-1">
                    Google Forms &bull; แบบประเมินระบบ
                </span>
                <h2 id="surveyModalTitle" class="text-base sm:text-lg font-bold text-slate-900 leading-snug font-['Inter','Sarabun']">
                    แบบประเมินประสิทธิภาพและการยอมรับระบบตรวจข้อสอบปรนัยอัจฉริยะ (MSU Score)
                </h2>
            </div>
        </div>
        <button type="button" onclick="closeSurveyModal()" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition-colors flex-shrink-0" title="ปิดหน้าต่าง">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Description -->
    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-5">
        ขอความอนุเคราะห์อาจารย์ ผู้ประเมิน และผู้ใช้งานทุกท่าน ร่วมตอบแบบประเมินความพึงพอใจและประสิทธิภาพ เพื่อนำข้อมูลไปปรับปรุงและพัฒนาระบบ MSU Scoring ต่อไป
    </p>

    <!-- QR Code Section -->
    <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-4 sm:p-5 flex flex-col items-center text-center mb-5">
        <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm mb-3">
            <img src="assets/survey_qr.png" 
                 alt="QR Code แบบประเมินประสิทธิภาพระบบ MSU Score" 
                 class="w-48 h-48 sm:w-56 sm:h-56 object-contain block mx-auto">
        </div>
        
        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 mb-1">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <span>สแกน QR Code ด้วยสมาร์ทโฟน</span>
        </div>
        <p class="text-[11px] text-slate-500">
            เปิดแอปพลิเคชันกล้องถ่ายรูป หรือ LINE เพื่อสแกนและตอบแบบประเมินได้ทันที
        </p>

        <!-- QR Download Actions -->
        <div class="flex flex-wrap items-center justify-center gap-2 mt-3 pt-3 border-t border-slate-200/80 w-full text-xs">
            <a href="assets/survey_qr.png" download="MSU_Scoring_Survey_QR.png" 
               class="inline-flex items-center gap-1 text-slate-600 hover:text-amber-700 py-1 px-2 rounded-lg hover:bg-slate-200/60 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>บันทึกรูป QR Code</span>
            </a>
            <span class="text-slate-300">&bull;</span>
            <a href="assets/survey_qr_card.png" download="MSU_Scoring_Survey_Card.png" 
               class="inline-flex items-center gap-1 text-slate-600 hover:text-amber-700 py-1 px-2 rounded-lg hover:bg-slate-200/60 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>บันทึกใบประกาศพร้อมพิมพ์</span>
            </a>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col gap-2.5">
        <a href="<?= htmlspecialchars($survey_form_url) ?>" 
           target="_blank" 
           rel="noopener noreferrer" 
           class="w-full bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 active:scale-[0.98] text-slate-950 font-bold py-3 px-5 rounded-xl shadow-md shadow-yellow-500/20 transition-all flex items-center justify-center gap-2 text-sm sm:text-base">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            <span>เปิดทำแบบประเมินในแท็บใหม่ (Google Forms)</span>
        </a>

        <div class="flex gap-2">
            <button type="button" 
                    onclick="copySurveyLink()" 
                    class="flex-1 bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-700 font-semibold py-2.5 px-4 rounded-xl transition-all text-xs sm:text-sm flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <span>คัดลอกลิงก์</span>
            </button>
            
            <button type="button" 
                    onclick="closeSurveyModal()" 
                    class="bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-slate-600 font-semibold py-2.5 px-4 rounded-xl transition-all text-xs sm:text-sm">
                ปิด
            </button>
        </div>
    </div>
</dialog>

<script>
function openSurveyModal() {
    const modal = document.getElementById('surveyModal');
    if (modal && typeof modal.showModal === 'function') {
        modal.showModal();
    }
}

function closeSurveyModal() {
    const modal = document.getElementById('surveyModal');
    if (modal && typeof modal.close === 'function') {
        modal.close();
    }
}

function copySurveyLink() {
    const surveyUrl = "<?= addslashes($survey_form_url) ?>";
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(surveyUrl).then(() => {
            if (typeof showToast === 'function') {
                showToast('คัดลอกลิงก์แบบประเมินเรียบร้อยแล้ว', 'success');
            } else {
                alert('คัดลอกลิงก์แบบประเมินเรียบร้อยแล้ว');
            }
        }).catch(() => fallbackCopy(surveyUrl));
    } else {
        fallbackCopy(surveyUrl);
    }
}

function fallbackCopy(text) {
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    try {
        document.execCommand('copy');
        if (typeof showToast === 'function') {
            showToast('คัดลอกลิงก์แบบประเมินเรียบร้อยแล้ว', 'success');
        } else {
            alert('คัดลอกลิงก์แบบประเมินเรียบร้อยแล้ว');
        }
    } catch (e) {
        prompt('คัดลอกลิงก์แบบประเมินด้านล่าง:', text);
    }
    document.body.removeChild(tempInput);
}
</script>
