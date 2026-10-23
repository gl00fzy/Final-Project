<?php
/**
 * MSU Scoring - Interactive User Guide & Quick-Start Modal Component (Hybrid Approach)
 * Visual 4-Step Infographic, Interactive Walkthrough, Image Lightbox, FAQs, and Full .docx Manual Download
 */
?>
<!-- ════ USER GUIDE & QUICK-START MODAL DIALOG ═════════════════════════════ -->
<dialog id="userGuideModal" aria-labelledby="userGuideModalTitle"
        class="bg-white shadow-2xl p-0 border border-slate-200/90 backdrop:bg-slate-950/75 backdrop:backdrop-blur-sm m-auto text-slate-800">
    
    <!-- Top MSU Accent Bar (Gold/Amber Gradient) -->
    <div class="h-1.5 sm:h-2 w-full bg-gradient-to-r from-yellow-400 via-amber-500 to-yellow-500 flex-shrink-0"></div>

    <!-- ── MODAL HEADER ────────────────────────────────────────────────────── -->
    <div class="p-3.5 sm:p-6 pb-3 sm:pb-4 border-b border-slate-100 flex items-start justify-between gap-3 sm:gap-4 flex-shrink-0 bg-slate-50/70">
        <div class="flex items-start gap-2.5 sm:gap-4 min-w-0">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-gradient-to-br from-yellow-400 to-amber-500 text-slate-950 flex items-center justify-center flex-shrink-0 shadow-md shadow-amber-500/20 mt-0.5">
                <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-0.5 sm:mb-1">
                    <span class="inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-800 bg-amber-100/80 border border-amber-300/80 px-2 py-0.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Quick-Start Guide
                    </span>
                    <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 bg-slate-200/70 px-1.5 sm:px-2 py-0.5 rounded-full">
                        v2.2
                    </span>
                </div>
                <h2 id="userGuideModalTitle" class="text-base sm:text-2xl font-extrabold text-slate-900 tracking-tight font-['Inter','Sarabun'] truncate">
                    คู่มือการใช้งานระบบ MSU Scoring
                </h2>
                <p class="text-[11px] sm:text-sm text-slate-600 mt-0.5 hidden sm:block">
                    ภาพรวม 4 ขั้นตอนการใช้งานจริง เทคนิคการตรวจข้อสอบความเร็วสูง และดาวน์โหลดคู่มือฉบับสมบูรณ์
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
            <!-- Header Direct Download Link -->
            <a href="download_manual.php" 
               class="hidden sm:inline-flex items-center gap-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-bold transition-all shadow-sm active:scale-95"
               title="ดาวน์โหลดไฟล์คู่มือ Word (.docx)">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>โหลดคู่มือ (.docx)</span>
            </a>

            <!-- Close Button -->
            <button type="button" onclick="closeUserGuideModal()" 
                    class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 rounded-xl transition-all cursor-pointer" 
                    title="ปิดหน้าต่าง (Esc)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- ── TAB NAVIGATION (SEGMENTED CONTROL) ──────────────────────────────── -->
    <div class="px-3.5 sm:px-6 pt-2.5 sm:pt-3 pb-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-1.5 sm:gap-2 overflow-x-auto scrollbar-none flex-shrink-0">
        <div class="flex items-center gap-1 sm:gap-2">
            <button type="button" id="tabBtn-workflow" onclick="switchGuideTab('workflow')"
                    class="guide-tab-btn active px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="hidden sm:inline">4 ขั้นตอนการใช้งาน (Workflow)</span>
                <span class="sm:hidden">4 ขั้นตอน</span>
            </button>
            <button type="button" id="tabBtn-features" onclick="switchGuideTab('features')"
                    class="guide-tab-btn px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                <span class="hidden sm:inline">ฟังก์ชันเด่น &amp; จุดแข็งระบบ</span>
                <span class="sm:hidden">ฟังก์ชันเด่น</span>
            </button>
            <button type="button" id="tabBtn-faqs" onclick="switchGuideTab('faqs')"
                    class="guide-tab-btn px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="hidden sm:inline">คำถามที่พบบ่อย (FAQs)</span>
                <span class="sm:hidden">FAQs</span>
            </button>
            <button type="button" id="tabBtn-download" onclick="switchGuideTab('download')"
                    class="guide-tab-btn px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="hidden sm:inline">ดาวน์โหลดคู่มือฉบับเต็ม</span>
                <span class="sm:hidden">ดาวน์โหลด</span>
            </button>
        </div>
    </div>

    <!-- ── TAB CONTENT BODY (SCROLLABLE) ──────────────────────────────────── -->
    <div id="guideContentBody" class="guide-content-scroll p-4 sm:p-7 space-y-5 sm:space-y-6">

        <!-- ════ TAB 1: 4-STEP WORKFLOW & INFOGRAPHIC ═══════════════════════ -->
        <div id="guideTabContent-workflow" class="guide-tab-pane space-y-4 sm:space-y-6">
            
            <!-- Quick Intro Banner -->
            <div class="bg-gradient-to-r from-amber-500/10 via-yellow-400/10 to-amber-500/5 border border-amber-300/60 rounded-xl sm:rounded-2xl p-3 sm:p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5 sm:gap-3">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <span class="text-xl sm:text-2xl">⚡</span>
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900">กระบวนการตรวจข้อสอบด่วนใน 4 ขั้นตอน</h4>
                        <p class="text-[11px] sm:text-xs text-slate-600">แตะหรือคลิกที่การ์ดขั้นตอนด้านล่าง เพื่อดูภาพหน้าจอจริงและคำแนะนำ</p>
                    </div>
                </div>
                <span class="text-[10px] sm:text-[11px] font-bold text-amber-800 bg-white/90 border border-amber-200 px-2.5 py-1 rounded-xl shadow-xs whitespace-nowrap">
                    ตรวจได้ทันที ไม่ต้องมีเครื่อง OMR
                </span>
            </div>

            <!-- 4-Step Interactive Stepper Grid (2x2 on Mobile, 4 columns on Desktop) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                
                <!-- STEP 1 CARD -->
                <button type="button" onclick="selectGuideStep(1)" id="stepCard-1"
                        class="guide-step-card active text-left p-3 sm:p-4 rounded-xl sm:rounded-2xl border-2 cursor-pointer relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="guide-step-badge w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-xs font-black flex items-center justify-center shadow-xs"
                                  style="background-color: #2563eb !important; color: #ffffff !important;">1</span>
                            <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-1.5 sm:px-2 py-0.5 rounded-md">เริ่มต้น</span>
                        </div>
                        <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 mb-1">สร้างชุดข้อสอบ &amp; เฉลย</h4>
                        <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed line-clamp-2 sm:line-clamp-none">
                            พิมพ์ค้นหาวิชา MBS 286 วิชา กำหนด 20-100 ข้อ และตั้งเฉลย ชุด A-D
                        </p>
                    </div>
                    <div class="mt-2.5 sm:mt-3 pt-2 sm:pt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px] sm:text-[11px] font-bold text-blue-600">
                        <span>ดูภาพหน้าจอ</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>

                <!-- STEP 2 CARD -->
                <button type="button" onclick="selectGuideStep(2)" id="stepCard-2"
                        class="guide-step-card text-left p-3 sm:p-4 rounded-xl sm:rounded-2xl border-2 cursor-pointer relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="guide-step-badge w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-xs font-black flex items-center justify-center shadow-xs"
                                  style="background-color: #f59e0b !important; color: #020617 !important;">2</span>
                            <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200 px-1.5 sm:px-2 py-0.5 rounded-md">พิมพ์</span>
                        </div>
                        <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 mb-1">พิมพ์กระดาษคำตอบ A4</h4>
                        <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed line-clamp-2 sm:line-clamp-none">
                            ดาวน์โหลด PDF ZipGrade-Style พิมพ์ลงกระดาษ A4 ธรรมดา 70-80 แกรม
                        </p>
                    </div>
                    <div class="mt-2.5 sm:mt-3 pt-2 sm:pt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px] sm:text-[11px] font-bold text-amber-700">
                        <span>ดูภาพหน้าจอ</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>

                <!-- STEP 3 CARD -->
                <button type="button" onclick="selectGuideStep(3)" id="stepCard-3"
                        class="guide-step-card text-left p-3 sm:p-4 rounded-xl sm:rounded-2xl border-2 cursor-pointer relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="guide-step-badge w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-xs font-black flex items-center justify-center shadow-xs"
                                  style="background-color: #059669 !important; color: #ffffff !important;">3</span>
                            <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 sm:px-2 py-0.5 rounded-md">สแกน</span>
                        </div>
                        <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 mb-1">สแกนตรวจกล้อง / PDF</h4>
                        <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed line-clamp-2 sm:line-clamp-none">
                            เล็งมาร์คเกอร์ 4 มุมตรวจสดทันที หรืออัปโหลดไฟล์ PDF ปึกใหญ่
                        </p>
                    </div>
                    <div class="mt-2.5 sm:mt-3 pt-2 sm:pt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px] sm:text-[11px] font-bold text-emerald-600">
                        <span>ดูภาพหน้าจอ</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>

                <!-- STEP 4 CARD -->
                <button type="button" onclick="selectGuideStep(4)" id="stepCard-4"
                        class="guide-step-card text-left p-3 sm:p-4 rounded-xl sm:rounded-2xl border-2 cursor-pointer relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="guide-step-badge w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-xs font-black flex items-center justify-center shadow-xs"
                                  style="background-color: #6366f1 !important; color: #ffffff !important;">4</span>
                            <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 sm:px-2 py-0.5 rounded-md">ผลลัพธ์</span>
                        </div>
                        <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 mb-1">ดูสถิติ &amp; ส่งออก Excel</h4>
                        <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed line-clamp-2 sm:line-clamp-none">
                            วิเคราะห์ค่าความยาก (p), อำนาจจำแนก (r) และดาวน์โหลด Excel/CSV
                        </p>
                    </div>
                    <div class="mt-2.5 sm:mt-3 pt-2 sm:pt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px] sm:text-[11px] font-bold text-indigo-600">
                        <span>ดูภาพหน้าจอ</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>

            </div>

            <!-- ── STEP DETAIL VIEWER (INTERACTIVE SHOWCASE) ────────────────── -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl sm:rounded-3xl p-4 sm:p-6 transition-all">
                
                <!-- DETAIL: STEP 1 -->
                <div id="stepDetail-1" class="guide-step-detail space-y-4">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 pb-3 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl font-black flex items-center justify-center text-xs sm:text-sm shadow-sm"
                                  style="background-color: #2563eb; color: #ffffff;">1</span>
                            <div>
                                <h3 class="text-sm sm:text-lg font-bold text-slate-900">ขั้นตอนที่ 1: การสร้างชุดข้อสอบและจัดการเฉลย</h3>
                                <p class="text-[11px] sm:text-xs text-slate-500">สร้างชุดข้อสอบใหม่ กำหนดรายละเอียดวิชา และเฉลยข้อสอบคู่ขนาน</p>
                            </div>
                        </div>
                        <a href="dashboard.php" class="w-full sm:w-auto text-center inline-flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded-xl transition-all shadow-sm">
                            <span>ไปยังหน้าสร้างชุดข้อสอบ</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-start">
                        <!-- Left Explanation & Checklist -->
                        <div class="lg:col-span-5 space-y-3 text-xs text-slate-700">
                            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
                                <h5 class="font-bold text-slate-900 flex items-center gap-1.5 text-xs">
                                    <span class="text-blue-600">✔</span> สิ่งที่ต้องทำในขั้นตอนนี้:
                                </h5>
                                <ul class="space-y-1.5 pl-1 text-slate-600">
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></span>
                                        <span>กดปุ่ม <b>"+ สร้างชุดข้อสอบใหม่"</b> บนหน้าแดชบอร์ด</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></span>
                                        <span>พิมพ์รหัสวิชา เช่น <i>0503...</i> เพื่อใช้ <b>MBS Autocomplete</b> เติมชื่อวิชาอัตโนมัติ</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></span>
                                        <span>กำหนดจำนวนข้อ (20, 40, 60 หรือ 100 ข้อ) และจำนวนชุดคู่ขนาน (Set A, B, C, D)</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></span>
                                        <span>คลิก <b>"จัดการเฉลย"</b> เพื่อฝนคำตอบที่ถูกต้อง ก ข ค ง หรือ จ</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-blue-50 border border-blue-200/80 rounded-xl p-3 text-[11px] text-blue-900">
                                <b>💡 เทคนิคขั้นสูง:</b> อาจารย์สามารถตั้งค่าเกณฑ์รายข้อ เช่น ข้อสอบที่มีคำตอบถูกหลายข้อ (OR / AND), ให้คะแนนพิเศษรายข้อ (เช่น ข้อละ 2 คะแนน), หรือหักคะแนนเมื่อตอบผิด (Penalty) เพื่อป้องกันการเดา
                            </div>
                        </div>

                        <!-- Right Screenshot Preview with Lightbox Trigger -->
                        <div class="lg:col-span-7 bg-white p-2 sm:p-3 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="relative overflow-hidden rounded-xl border border-slate-100 bg-slate-900/5 cursor-pointer group"
                                 onclick="openGuideLightbox('assets/guide/step1_create_exam.png', 'รูปที่ 1: หน้าต่างสร้างชุดข้อสอบ พร้อมระบบค้นหารหัสวิชา MBS')">
                                <img src="assets/guide/step1_create_exam.png" 
                                     alt="หน้าต่างสร้างชุดข้อสอบและค้นหารหัสวิชา" 
                                     class="w-full h-auto object-cover rounded-xl transition-transform duration-300 group-hover:scale-[1.02]">
                                <div class="absolute inset-0 bg-slate-950/25 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="bg-slate-900/85 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md">
                                        <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        <span>คลิกเพื่อดูภาพขยาย</span>
                                    </span>
                                </div>
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 text-center mt-2 font-medium">
                                รูปที่ 1: หน้าต่างสร้างชุดข้อสอบ พร้อมระบบค้นหารายวิชาของคณะ MBS 286 วิชาอัตโนมัติ (แตะภาพเพื่อขยาย)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- DETAIL: STEP 2 -->
                <div id="stepDetail-2" class="guide-step-detail space-y-4 hidden">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 pb-3 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl font-black flex items-center justify-center text-xs sm:text-sm shadow-sm"
                                  style="background-color: #f59e0b; color: #020617;">2</span>
                            <div>
                                <h3 class="text-sm sm:text-lg font-bold text-slate-900">ขั้นตอนที่ 2: การพิมพ์กระดาษคำตอบมาตรฐาน A4</h3>
                                <p class="text-[11px] sm:text-xs text-slate-500">สร้างไฟล์ PDF กระดาษคำตอบ ZipGrade-Style และพิมพ์ด้วยเครื่องพิมพ์สำนักงาน</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-900 text-xs font-bold px-3 py-1.5 rounded-xl border border-amber-300">
                            <span>📄 ใช้กระดาษ A4 ธรรมดา 70-80 แกรม</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-start">
                        <!-- Left Explanation & Checklist -->
                        <div class="lg:col-span-5 space-y-3 text-xs text-slate-700">
                            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
                                <h5 class="font-bold text-slate-900 flex items-center gap-1.5 text-xs">
                                    <span class="text-amber-600">✔</span> ข้อปฏิบัติในการพิมพ์กระดาษคำตอบ:
                                </h5>
                                <ul class="space-y-1.5 pl-1 text-slate-600">
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 flex-shrink-0"></span>
                                        <span>กดปุ่ม <b>"🖨️ พิมพ์กระดาษคำตอบ"</b> บนการ์ดชุดข้อสอบเพื่อดาวน์โหลดไฟล์ PDF</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 flex-shrink-0"></span>
                                        <span>ตั้งค่าการพิมพ์เป็น <b>ขนาดจริง (Actual Size 100%)</b> ไม่เลือก Fit to Printable Area</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 flex-shrink-0"></span>
                                        <span>ตรวจสอบว่า <b>จุดมาร์คเกอร์สี่เหลี่ยมสีดำทั้ง 4 มุม</b> ชัดเจนและไม่ถูกตัดขอบกระดาษ</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 flex-shrink-0"></span>
                                        <span>แนะนำให้นิสิตใช้ <b>ดินสอ 2B หรือปากกาสีดำ/น้ำเงินเข้ม</b> ในการฝนคำตอบ</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-amber-50 border border-amber-200/80 rounded-xl p-3 text-[11px] text-amber-900">
                                <b>⚠️ ข้อควรระวัง:</b> ห้ามยับ พับ หรือตัดมุมกระดาษคำตอบ เพราะอัลกอริทึม Computer Vision จำเป็นต้องใช้มาร์คเกอร์ 4 มุมในการดัดระนาบภาพ (Perspective Transform)
                            </div>
                        </div>

                        <!-- Right Screenshot Preview -->
                        <div class="lg:col-span-7 bg-white p-2 sm:p-3 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="relative overflow-hidden rounded-xl border border-slate-100 bg-slate-900/5 cursor-pointer group"
                                 onclick="openGuideLightbox('assets/guide/step2_answer_sheet.png', 'รูปที่ 2: แผ่นกระดาษคำตอบมาตรฐาน A4 พร้อมจุด Fiducial Markers 4 มุม')">
                                <img src="assets/guide/step2_answer_sheet.png" 
                                     alt="โครงสร้างกระดาษคำตอบมาตรฐาน A4" 
                                     class="w-full h-auto object-cover rounded-xl transition-transform duration-300 group-hover:scale-[1.02]">
                                <div class="absolute inset-0 bg-slate-950/25 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="bg-slate-900/85 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md">
                                        <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        <span>คลิกเพื่อดูภาพขยาย</span>
                                    </span>
                                </div>
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 text-center mt-2 font-medium">
                                รูปที่ 2: ตัวอย่างแผ่นกระดาษคำตอบมาตรฐาน พร้อมจุด Fiducial Markers 4 มุม และโซนรหัสนิสิต 11 หลัก
                            </p>
                        </div>
                    </div>
                </div>

                <!-- DETAIL: STEP 3 -->
                <div id="stepDetail-3" class="guide-step-detail space-y-4 hidden">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 pb-3 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl font-black flex items-center justify-center text-xs sm:text-sm shadow-sm"
                                  style="background-color: #059669; color: #ffffff;">3</span>
                            <div>
                                <h3 class="text-sm sm:text-lg font-bold text-slate-900">ขั้นตอนที่ 3: การสแกนตรวจกระดาษคำตอบผ่านกล้อง หรือ Batch PDF</h3>
                                <p class="text-[11px] sm:text-xs text-slate-500">ใช้กล้องสมาร์ตโฟน/โน้ตบุ๊กตรวจสด หรืออัปโหลดไฟล์ PDF ปึกใหญ่จากเครื่องถ่ายเอกสาร</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1.5 rounded-xl border border-emerald-300">
                            <span>📷 Real-time OMR Scanner HUD</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-start">
                        <!-- Left Explanation & Checklist -->
                        <div class="lg:col-span-5 space-y-3 text-xs text-slate-700">
                            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
                                <h5 class="font-bold text-slate-900 flex items-center gap-1.5 text-xs">
                                    <span class="text-emerald-600">✔</span> วิธีการสแกนตรวจที่ถูกต้อง:
                                </h5>
                                <ul class="space-y-1.5 pl-1 text-slate-600">
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></span>
                                        <span>กดปุ่ม <b>"📷 สแกนตรวจข้อสอบ"</b> และอนุญาตให้เบราว์เซอร์เข้าถึงกล้อง (WebRTC)</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></span>
                                        <span>จัดวางกระดาษคำตอบให้ <b>จุดมาร์คเกอร์ 4 มุม</b> อยู่ภายในกรอบเล็งบนหน้าจอ</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></span>
                                        <span>เมื่อระบบล็อคตำแหน่งได้ จะส่งเสียง <b>"Beep"</b> และแสดงกรอบสี่เหลี่ยมสีเขียวพร้อมคะแนนทันที</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></span>
                                        <span><b>กรณีมีข้อสอบ 100-500 แผ่น:</b> คลิกปุ่ม <b>"อัปโหลด PDF"</b> เพื่อตรวจอัตโนมัติทั้งปึก</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-emerald-50 border border-emerald-200/80 rounded-xl p-3 text-[11px] text-emerald-900">
                                <b>💡 ข้อแนะนำด้านแสง:</b> วางกระดาษบนโต๊ะที่มีสีตัดกับกระดาษ (เช่น โต๊ะสีเข้ม) และหลีกเลี่ยงแสงสะท้อนจากหลอดไฟตรงเหนือกระดาษ เพื่อให้ Computer Vision อ่านค่าได้เร็วที่สุด
                            </div>
                        </div>

                        <!-- Right Screenshot Preview -->
                        <div class="lg:col-span-7 bg-white p-2 sm:p-3 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="relative overflow-hidden rounded-xl border border-slate-100 bg-slate-900/5 cursor-pointer group"
                                 onclick="openGuideLightbox('assets/guide/step3_scanner.png', 'รูปที่ 3: หน้าจอ HUD OMR Scanner สไตล์ ZipGrade แสดงกรอบเล็ง Viewfinder')">
                                <img src="assets/guide/step3_scanner.png" 
                                     alt="หน้าจอระบบสแกนกล้องสด Viewfinder HUD" 
                                     class="w-full h-auto object-cover rounded-xl transition-transform duration-300 group-hover:scale-[1.02]">
                                <div class="absolute inset-0 bg-slate-950/25 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="bg-slate-900/85 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md">
                                        <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        <span>คลิกเพื่อดูภาพขยาย</span>
                                    </span>
                                </div>
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 text-center mt-2 font-medium">
                                รูปที่ 3: หน้าจอ HUD OMR Scanner สไตล์ ZipGrade แสดงกรอบเล็ง Viewfinder และผลคะแนนทันที
                            </p>
                        </div>
                    </div>
                </div>

                <!-- DETAIL: STEP 4 -->
                <div id="stepDetail-4" class="guide-step-detail space-y-4 hidden">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 pb-3 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl font-black flex items-center justify-center text-xs sm:text-sm shadow-sm"
                                  style="background-color: #6366f1; color: #ffffff;">4</span>
                            <div>
                                <h3 class="text-sm sm:text-lg font-bold text-slate-900">ขั้นตอนที่ 4: การดูผลคะแนน สถิติวิเคราะห์ข้อสอบ CTT และส่งออก</h3>
                                <p class="text-[11px] sm:text-xs text-slate-500">ตรวจสอบกราฟการกระจายคะแนน คุณภาพข้อสอบรายข้อ และดาวน์โหลดไฟล์ Excel/CSV</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1.5 rounded-xl border border-indigo-300">
                            <span>📊 Classical Test Theory (CTT)</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-start">
                        <!-- Left Explanation & Checklist -->
                        <div class="lg:col-span-5 space-y-3 text-xs text-slate-700">
                            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2">
                                <h5 class="font-bold text-slate-900 flex items-center gap-1.5 text-xs">
                                    <span class="text-indigo-600">✔</span> รายงานสถิติที่ระบบสรุปให้:
                                </h5>
                                <ul class="space-y-1.5 pl-1 text-slate-600">
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></span>
                                        <span><b>สถิติภาพรวม:</b> คะแนนเฉลี่ย (Mean), ส่วนเบี่ยงเบนมาตรฐาน (S.D.), คะแนนสูงสุด/ต่ำสุด</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></span>
                                        <span><b>แผนภูมิ Histogram:</b> แสดงการกระจายตัวของคะแนนนิสิตทั้งห้อง</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></span>
                                        <span><b>Item Analysis รายข้อ:</b> ค่าความยากง่าย (p-Value), อำนาจจำแนก (r-Value), และวิเคราะห์ตัวลวง</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></span>
                                        <span><b>ตรวจสอบย้อนหลัง:</b> กดดูภาพถ่ายกระดาษคำตอบต้นฉบับของนิสิตแต่ละคนได้</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-indigo-50 border border-indigo-200/80 rounded-xl p-3 text-[11px] text-indigo-900">
                                <b>📥 ส่งออกข้อมูล:</b> กดปุ่ม "ส่งออก Excel" หรือ "ส่งออก CSV" เพื่อนำคะแนนเข้าสู่ระบบทะเบียนและประมวลผลตัดเกรดของมหาวิทยาลัยได้ทันที
                            </div>
                        </div>

                        <!-- Right Screenshot Preview -->
                        <div class="lg:col-span-7 bg-white p-2 sm:p-3 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="relative overflow-hidden rounded-xl border border-slate-100 bg-slate-900/5 cursor-pointer group"
                                 onclick="openGuideLightbox('assets/guide/step4_analysis.png', 'รูปที่ 4: ตารางวิเคราะห์คุณภาพข้อสอบเชิงสถิติรายข้อ (Item Analysis)')">
                                <img src="assets/guide/step4_analysis.png" 
                                     alt="หน้าจอการวิเคราะห์ข้อสอบเชิงสถิติ CTT" 
                                     class="w-full h-auto object-cover rounded-xl transition-transform duration-300 group-hover:scale-[1.02]">
                                <div class="absolute inset-0 bg-slate-950/25 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="bg-slate-900/85 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md">
                                        <svg class="w-3.5 h-3.5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        <span>คลิกเพื่อดูภาพขยาย</span>
                                    </span>
                                </div>
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 text-center mt-2 font-medium">
                                รูปที่ 4: ตารางวิเคราะห์คุณภาพข้อสอบเชิงสถิติรายข้อ (Item Analysis) แสดงค่า p, ค่า r และการเลือกตัวลวง
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- ════ TAB 2: KEY FEATURES ════════════════════════════════════════ -->
        <div id="guideTabContent-features" class="guide-tab-pane space-y-4 sm:space-y-5 hidden">
            <div class="border-b border-slate-200 pb-3">
                <h3 class="text-sm sm:text-lg font-bold text-slate-900">ฟังก์ชันเด่นและความสามารถหลัก (Key Capabilities)</h3>
                <p class="text-[11px] sm:text-xs text-slate-500">นวัตกรรมตรวจข้อสอบอัจฉริยะที่ออกแบบมาเพื่อคณาจารย์มหาวิทยาลัยมหาสารคาม</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                
                <!-- Feature 1 -->
                <div class="guide-feature-card bg-white p-4 rounded-2xl border border-slate-200 shadow-xs cursor-default">
                    <div class="guide-feature-icon w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold mb-3 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 mb-1">Real-time Camera OMR</h4>
                    <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed">
                        ตรวจจับจุดมาร์คเกอร์ 4 มุมด้วย OpenCV.js ปรับภาพเอียงอัตโนมัติ ตรวจจับคำตอบและรหัสนิสิตทันทีผ่านกล้องเว็บแคมหรือมือถือ
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="guide-feature-card bg-white p-4 rounded-2xl border border-slate-200 shadow-xs cursor-default">
                    <div class="guide-feature-icon w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold mb-3 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 mb-1">Auto-Regrade Engine</h4>
                    <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed">
                        กรณีแก้ไขเฉลยข้อสอบหรือเปลี่ยนเกณฑ์คะแนนในภายหลัง ระบบจะคำนวณและอัปเดตคะแนนนิสิตทุกคนใหม่ให้อัตโนมัติ โดยไม่ต้องสแกนซ้ำ
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="guide-feature-card bg-white p-4 rounded-2xl border border-slate-200 shadow-xs cursor-default">
                    <div class="guide-feature-icon w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold mb-3 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 mb-1">Batch PDF Scanning</h4>
                    <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed">
                        รองรับการสแกนกระดาษคำตอบปึกใหญ่ผ่านเครื่องถ่ายเอกสาร แปลงเป็นไฟล์ PDF แล้วอัปโหลดตรวจพร้อมกันสูงสุด 200 หน้า / 50MB
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="guide-feature-card bg-white p-4 rounded-2xl border border-slate-200 shadow-xs cursor-default">
                    <div class="guide-feature-icon w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold mb-3 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 mb-1">Item &amp; Distractor Analysis</h4>
                    <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed">
                        วิเคราะห์ความยากง่าย (p), อำนาจจำแนก (r) และสัดส่วนตัวเลือก ก-จ ตามทฤษฎีการทดสอบดั้งเดิม (CTT) พร้อมคำแนะนำการปรับปรุงข้อสอบ
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="guide-feature-card bg-white p-4 rounded-2xl border border-slate-200 shadow-xs cursor-default">
                    <div class="guide-feature-icon w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold mb-3 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 mb-1">Exam Sharing</h4>
                    <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed">
                        แชร์ชุดข้อสอบให้อาจารย์ผู้สอนร่วมผ่านอีเมล @msu.ac.th เพื่อให้ช่วยกันสแกนตรวจกระดาษคำตอบ หรือร่วมดูผลสถิติคลิกเดียว
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="guide-feature-card bg-white p-4 rounded-2xl border border-slate-200 shadow-xs cursor-default">
                    <div class="guide-feature-icon w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold mb-3 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 mb-1">1-Click Demo Login</h4>
                    <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed">
                        ปุ่มทดลองใช้งานทันที 1-Click สำหรับกรรมการและผู้ประเมินระบบ พร้อมชุดข้อสอบและข้อมูลตัวอย่างครบครัน
                    </p>
                </div>

            </div>
        </div>

        <!-- ════ TAB 3: FAQS & TROUBLESHOOTING ══════════════════════════════ -->
        <div id="guideTabContent-faqs" class="guide-tab-pane space-y-3 sm:space-y-4 hidden">
            <div class="border-b border-slate-200 pb-3">
                <h3 class="text-sm sm:text-lg font-bold text-slate-900">คำถามและข้อผิดพลาดที่พบบ่อย (Troubleshooting &amp; FAQs)</h3>
                <p class="text-[11px] sm:text-xs text-slate-500">วิธีแก้ไขปัญหาเบื้องต้นเกี่ยวกับการสแกน กล้อง และการจัดเตรียมกระดาษคำตอบ</p>
            </div>

            <div class="space-y-2.5 sm:space-y-3 text-xs">
                
                <!-- FAQ 1 -->
                <details class="bg-white rounded-xl sm:rounded-2xl border border-slate-200 p-3.5 sm:p-4 group" open>
                    <summary class="font-bold text-slate-900 text-xs sm:text-sm cursor-pointer flex items-center justify-between gap-2">
                        <span>📷 เปิดหน้าจอ Scanner แล้วกล้องไม่ติด หรือจอมืด เกิดจากอะไร?</span>
                        <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="mt-2 text-slate-600 leading-relaxed space-y-1.5 pl-1 pt-2 border-t border-slate-100 text-[11px] sm:text-xs">
                        <p>1. ตรวจสอบว่าเบราว์เซอร์ได้รับอนุญาตให้เข้าถึงกล้องแล้วหรือไม่ โดยดูที่ไอคอนรูปกล้องหรือแม่กุญแจบนแถบ URL ด้านบน</p>
                        <p>2. ตรวจสอบว่ามีโปรแกรมอื่นเปิดใช้งานกล้องอยู่หรือไม่ (เช่น Zoom, Google Meet, Teams) ให้ปิดโปรแกรมเหล่านั้นก่อน</p>
                        <p>3. หากใช้งานบนสมาร์ตโฟน แนะนำให้เปิดผ่าน Chrome (บน Android) หรือ Safari (บน iOS) และตรวจสอบว่าเปิดใช้งานกล้องในโหมด HTTPS</p>
                    </div>
                </details>

                <!-- FAQ 2 -->
                <details class="bg-white rounded-xl sm:rounded-2xl border border-slate-200 p-3.5 sm:p-4 group">
                    <summary class="font-bold text-slate-900 text-xs sm:text-sm cursor-pointer flex items-center justify-between gap-2">
                        <span>🔍 กล้องสแกนไม่ยอมตรวจจับ หรือไม่ขึ้นกรอบสีเขียว แก้ไขอย่างไร?</span>
                        <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="mt-2 text-slate-600 leading-relaxed space-y-1.5 pl-1 pt-2 border-t border-slate-100 text-[11px] sm:text-xs">
                        <p>1. <b>ตรวจสอบมาร์คเกอร์ 4 มุม:</b> มาร์คเกอร์สี่เหลี่ยมสีดำทั้ง 4 มุมต้องอยู่ครบถ้วนในจอภาพ และไม่ถูกนิ้วมือบัง</p>
                        <p>2. <b>หลีกเลี่ยงแสงสะท้อน:</b> หากกระดาษสะท้อนแสงไฟนีออนตรงๆ หมึกพิมพ์จะเกิดแสงวาบ ให้เอียงมุมกล้องเล็กน้อยหรือขยับออกจากแนวสะท้อน</p>
                        <p>3. <b>พื้นหลัง:</b> วางกระดาษบนโต๊ะที่มีสีตัดกัน (เช่น โต๊ะสีไม้เข้ม) เพื่อให้ระบบแยกขอบกระดาษได้ง่ายขึ้น</p>
                    </div>
                </details>

                <!-- FAQ 3 -->
                <details class="bg-white rounded-xl sm:rounded-2xl border border-slate-200 p-3.5 sm:p-4 group">
                    <summary class="font-bold text-slate-900 text-xs sm:text-sm cursor-pointer flex items-center justify-between gap-2">
                        <span>📝 หากกระดาษคำตอบฉีกขาด หรือนิสิตฝนจางมาก จะทำอย่างไร?</span>
                        <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="mt-2 text-slate-600 leading-relaxed space-y-1.5 pl-1 pt-2 border-t border-slate-100 text-[11px] sm:text-xs">
                        <p>อาจารย์สามารถใช้ฟังก์ชัน <b>"กรอกคะแนนด้วยตนเอง (Manual Score Entry)"</b> โดยคลิกที่ปุ่ม 'กรอกคะแนน' บนหน้าจอ Scanner เพื่อระบุรหัสนิสิตและบันทึกคะแนนเข้าสู่ระบบโดยตรงได้ทันที</p>
                    </div>
                </details>

                <!-- FAQ 4 -->
                <details class="bg-white rounded-xl sm:rounded-2xl border border-slate-200 p-3.5 sm:p-4 group">
                    <summary class="font-bold text-slate-900 text-xs sm:text-sm cursor-pointer flex items-center justify-between gap-2">
                        <span>📊 คะแนนที่ได้จะนำไปส่งฝ่ายทะเบียนหรือตัดเกรดอย่างไร?</span>
                        <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="mt-2 text-slate-600 leading-relaxed space-y-1.5 pl-1 pt-2 border-t border-slate-100 text-[11px] sm:text-xs">
                        <p>ในหน้ารายงานผลคะแนน (Results) มีปุ่ม <b>"ส่งออก Excel (.xlsx)"</b> และ <b>"ส่งออก CSV"</b> ซึ่งมีคอลัมน์รหัสนิสิต ชุดข้อสอบ คะแนน และวันเวลาที่สแกน พร้อมนำไปใช้ตัดเกรดในโปรแกรม Excel หรือนำเข้าสู่ระบบตัดเกรดของมหาวิทยาลัยได้ทันที</p>
                    </div>
                </details>

            </div>
        </div>

        <!-- ════ TAB 4: FULL MANUAL DOWNLOAD ════════════════════════════════ -->
        <div id="guideTabContent-download" class="guide-tab-pane space-y-4 sm:space-y-5 hidden">
            
            <!-- Robust Dark Hero Card with inline fallback styles -->
            <div class="guide-download-card rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-xl relative overflow-hidden"
                 style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #1e1b4b 100%) !important; color: #ffffff !important;">
                
                <div class="absolute -right-8 -bottom-8 w-64 h-64 bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5 sm:gap-6 relative z-10">
                    <div class="flex items-start gap-3.5 sm:gap-4">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-500/30"
                             style="background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%) !important; color: #ffffff !important;">
                            <svg class="w-6 h-6 sm:w-9 sm:h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="inline-block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-300 bg-amber-400/20 border border-amber-400/30 px-2 sm:px-2.5 py-0.5 rounded-full mb-1 sm:mb-1.5">
                                Official Document • Full Version
                            </span>
                            <h3 class="text-base sm:text-2xl font-black tracking-tight" style="color: #ffffff !important;">
                                คู่มือการใช้งานระบบตรวจข้อสอบ MSU Scoring ฉบับสมบูรณ์
                            </h3>
                            <p class="text-[11px] sm:text-sm mt-1 max-w-xl leading-relaxed" style="color: #cbd5e1 !important;">
                                เอกสาร Microsoft Word (.docx) ความละเอียดสูง จัดพิมพ์ด้วยฟอนต์ราชการ TH SarabunPSK จัดการกระจายคำภาษาไทยอย่างเป็นธรรมชาติ พร้อม 17 ภาพประกอบชี้ตำแหน่ง UI ทุกฟังก์ชัน
                            </p>
                        </div>
                    </div>

                    <a href="download_manual.php"
                       class="guide-dl-btn w-full sm:w-auto flex-shrink-0 text-slate-950 font-black px-5 sm:px-6 py-3 sm:py-3.5 rounded-2xl text-xs sm:text-sm transition-all shadow-lg shadow-amber-400/25 flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap active:scale-95">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-slate-950 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>ดาวน์โหลดคู่มือ (.docx)</span>
                    </a>
                </div>

                <!-- Document Specs Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-slate-700/80 text-xs">
                    <div class="p-2.5 sm:p-3 rounded-xl" style="background: rgba(30, 41, 59, 0.75) !important; border: 1px solid rgba(71, 85, 105, 0.6) !important;">
                        <span class="block text-[10px] sm:text-[11px]" style="color: #94a3b8 !important;">รูปแบบไฟล์:</span>
                        <span class="text-xs sm:text-sm font-bold block mt-0.5" style="color: #ffffff !important;">Word (.docx)</span>
                    </div>
                    <div class="p-2.5 sm:p-3 rounded-xl" style="background: rgba(30, 41, 59, 0.75) !important; border: 1px solid rgba(71, 85, 105, 0.6) !important;">
                        <span class="block text-[10px] sm:text-[11px]" style="color: #94a3b8 !important;">ขนาดไฟล์:</span>
                        <span class="text-xs sm:text-sm font-bold block mt-0.5" style="color: #ffffff !important;">~5.14 MB</span>
                    </div>
                    <div class="p-2.5 sm:p-3 rounded-xl" style="background: rgba(30, 41, 59, 0.75) !important; border: 1px solid rgba(71, 85, 105, 0.6) !important;">
                        <span class="block text-[10px] sm:text-[11px]" style="color: #94a3b8 !important;">โครงสร้างเนื้อหา:</span>
                        <span class="text-xs sm:text-sm font-bold block mt-0.5" style="color: #ffffff !important;">6 บท 36 หัวข้อ</span>
                    </div>
                    <div class="p-2.5 sm:p-3 rounded-xl" style="background: rgba(30, 41, 59, 0.75) !important; border: 1px solid rgba(71, 85, 105, 0.6) !important;">
                        <span class="block text-[10px] sm:text-[11px]" style="color: #94a3b8 !important;">รูปภาพประกอบ:</span>
                        <span class="text-xs sm:text-sm font-bold block mt-0.5" style="color: #ffffff !important;">17 ภาพ HUD UI</span>
                    </div>
                </div>
            </div>

            <!-- Chapter Overview Grid -->
            <div class="bg-white rounded-xl sm:rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 mb-2.5 sm:mb-3">สารบัญและโครงสร้างเนื้อหาในคู่มือฉบับเต็ม</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 text-[11px] sm:text-xs text-slate-600">
                    <div class="p-2.5 sm:p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                        <b class="text-slate-900 block mb-0.5">บทที่ 1: ภาพรวมระบบและสถาปัตยกรรม</b>
                        <span>วัตถุประสงค์, ฟังก์ชันเด่น 8 ประการ, ตารางสิทธิ์อาจารย์/Admin, ความต้องการระบบ</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                        <b class="text-slate-900 block mb-0.5">บทที่ 2: การเข้าสู่ระบบและการลงทะเบียน</b>
                        <span>การล็อกอิน, การสมัครสมาชิก, 1-Click Demo Mode, Google OAuth 2.0 (@msu.ac.th)</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                        <b class="text-slate-900 block mb-0.5">บทที่ 3: คู่มือสำหรับอาจารย์ผู้สอน</b>
                        <span>การสร้างข้อสอบ, ค้นหาวิชา MBS, เฉลยคู่ขนาน A-D, สแกนกล้อง, สแกน PDF, วิเคราะห์ CTT</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                        <b class="text-slate-900 block mb-0.5">บทที่ 4: คู่มือสำหรับผู้ดูแลระบบ (Admin)</b>
                        <span>แดชบอร์ดสถิติรวม, อนุมัติสมาชิกใหม่, จัดการรหัสคำเชิญ, ล้างไฟล์ขยะ, บันทึกความปลอดภัย</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                        <b class="text-slate-900 block mb-0.5">บทที่ 5: แนวทางแก้ไขปัญหาที่พบบ่อย (FAQs)</b>
                        <span>เทคนิคการจัดแสง, การแก้ปัญหามาร์คเกอร์, การตั้งค่ากล้องบนมือถือและโน้ตบุ๊ก</span>
                    </div>
                    <div class="p-2.5 sm:p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                        <b class="text-slate-900 block mb-0.5">บทที่ 6: ช่องทางการติดต่อและความช่วยเหลือ</b>
                        <span>คณะการบัญชีและการจัดการ (MBS) ร่วมกับสำนักคอมพิวเตอร์ มหาวิทยาลัยมหาสารคาม</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ── MODAL FOOTER ────────────────────────────────────────────────────── -->
    <div class="p-3 sm:p-5 border-t border-slate-200/90 bg-slate-50 flex items-center justify-between gap-2.5 sm:gap-3 flex-shrink-0">
        <div class="flex items-center gap-1.5 sm:gap-2 text-[11px] sm:text-xs text-slate-500">
            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="hidden sm:inline">กดปุ่ม <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded text-[10px] font-mono shadow-2xs">Esc</kbd> หรือคลิกพื้นที่ด้านนอกเพื่อปิด</span>
            <span class="sm:hidden">แตะพื้นหลังเพื่อปิด</span>
        </div>

        <div class="flex items-center gap-2">
            <a href="download_manual.php" 
               class="bg-blue-600 hover:bg-blue-700 active:scale-95 text-white px-3 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>โหลดคู่มือ Word</span>
            </a>
            <button type="button" onclick="closeUserGuideModal()" 
                    class="bg-slate-200 hover:bg-slate-300 active:scale-95 text-slate-700 px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer">
                ปิด
            </button>
        </div>
    </div>

</dialog>

<!-- ════ IMAGE LIGHTBOX POPUP (HTML5 Dialog Top-Layer) ═════════════════════ -->
<dialog id="guideLightbox" 
        class="bg-transparent backdrop:bg-slate-950/85 backdrop:backdrop-blur-md p-0 m-auto border-0 max-w-5xl max-h-[94vh] overflow-visible outline-none">
    <div class="relative flex flex-col items-center p-3 sm:p-5" onclick="event.stopPropagation()">
        <!-- Close Button -->
        <button type="button" onclick="closeGuideLightbox()"
                class="self-end mb-2.5 text-white/90 hover:text-white bg-slate-800/90 hover:bg-slate-700 border border-white/25 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-lg active:scale-95">
            <span>ปิดภาพขยาย (Esc)</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <!-- Lightbox Image -->
        <img id="guideLightboxImg" src="" alt="ภาพขยายคู่มือ" class="max-w-[92vw] sm:max-w-4xl max-h-[76vh] object-contain rounded-xl sm:rounded-2xl shadow-2xl border border-white/20 bg-slate-900">
        <p id="guideLightboxCaption" class="text-xs sm:text-sm text-slate-200 text-center mt-3 font-semibold px-4 py-1.5 rounded-full bg-slate-900/85 backdrop-blur-sm border border-white/10 shadow-md"></p>
    </div>
</dialog>

<!-- ── STYLES & INTERACTION SCRIPT ─────────────────────────────────────── -->
<style>
/* Dialog display & explicit dimensions */
@keyframes guideModalFadeIn {
    0% {
        opacity: 0;
        transform: scale(0.96) translateY(12px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes guideTabFadeIn {
    0% {
        opacity: 0;
        transform: translateY(8px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes guideBadgePulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.12);
        box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
    }
}

@keyframes goldenShimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

#userGuideModal:not([open]) {
    display: none !important;
}

#userGuideModal[open] {
    display: flex !important;
    flex-direction: column !important;
    width: 95vw !important;
    max-width: 64rem !important; /* max-w-5xl */
    height: 90vh !important;
    max-height: 90vh !important;
    margin: auto !important;
    padding: 0 !important;
    border-radius: 1.5rem !important;
    overflow: hidden !important;
    box-sizing: border-box !important;
    animation: guideModalFadeIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
}

@media (max-width: 640px) {
    #userGuideModal[open] {
        width: 96vw !important;
        max-width: 96vw !important;
        height: 94vh !important;
        max-height: 94vh !important;
        margin: 3vh auto !important;
        border-radius: 1.25rem !important;
    }
}

/* Scrollable Content Body with smooth scrolling and containment */
#userGuideModal .guide-content-scroll {
    flex: 1 1 0% !important;
    min-height: 0 !important;
    height: 100% !important;
    overflow-y: auto !important;
    overscroll-behavior: contain !important;
    -webkit-overflow-scrolling: touch !important;
    scroll-behavior: smooth !important;
}

/* Clean custom scrollbar */
#userGuideModal .guide-content-scroll::-webkit-scrollbar {
    width: 7px;
}
#userGuideModal .guide-content-scroll::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 9999px;
}
#userGuideModal .guide-content-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
    border: 2px solid #f8fafc;
}
#userGuideModal .guide-content-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Tab pane animation */
.guide-tab-pane:not(.hidden) {
    animation: guideTabFadeIn 0.24s ease-out forwards;
}

.guide-step-detail:not(.hidden) {
    animation: guideTabFadeIn 0.26s ease-out forwards;
}

/* Guide Tab Button Styles */
.guide-tab-btn {
    color: #475569;
    background-color: transparent;
    border: 1px solid transparent;
}
.guide-tab-btn:hover {
    color: #0f172a;
    background-color: #e2e8f0;
}
.guide-tab-btn.active {
    color: #0f172a;
    background-color: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

/* Step Card Styles & Micro-Interactions */
.guide-step-card {
    background-color: #ffffff;
    border-color: #e2e8f0;
    transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
}
.guide-step-card:hover {
    transform: translateY(-2px);
    border-color: #f59e0b;
    box-shadow: 0 8px 16px -4px rgba(245, 158, 11, 0.15);
}
.guide-step-card.active {
    background-color: #ffffff;
    border-color: #f59e0b;
    box-shadow: 0 10px 20px -3px rgba(245, 158, 11, 0.2);
}
.guide-step-card.active .guide-step-badge {
    animation: guideBadgePulse 2.2s infinite ease-in-out;
}

/* Shimmer Download Button */
.guide-dl-btn {
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 50%, #f59e0b 100%);
    background-size: 200% auto;
}
.guide-dl-btn:hover {
    animation: goldenShimmer 2s linear infinite;
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.4);
}

/* Feature Cards Hover effect */
.guide-feature-card {
    transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
}
.guide-feature-card:hover {
    transform: translateY(-3px);
    border-color: #f59e0b !important;
    box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.08);
}
.guide-feature-card:hover .guide-feature-icon {
    transform: scale(1.1) rotate(-3deg);
}
.guide-feature-icon {
    transition: transform 0.22s ease;
}
</style>

<script>
/**
 * Interactive User Guide Modal Handler
 */
function openUserGuideModal(initialStep = 1, initialTab = 'workflow') {
    const modal = document.getElementById('userGuideModal');
    if (!modal) return;
    
    switchGuideTab(initialTab);
    selectGuideStep(initialStep);
    
    // Prevent background page scrolling
    document.body.style.overflow = 'hidden';

    if (typeof modal.showModal === 'function') {
        modal.showModal();
    } else {
        modal.setAttribute('open', '');
    }

    // Reset scroll position to top
    const contentBody = document.getElementById('guideContentBody');
    if (contentBody) contentBody.scrollTop = 0;
}

function closeUserGuideModal() {
    const modal = document.getElementById('userGuideModal');
    if (!modal) return;

    // Restore background page scrolling
    document.body.style.overflow = '';

    if (typeof modal.close === 'function') {
        modal.close();
    } else {
        modal.removeAttribute('open');
    }
}

function switchGuideTab(tabName) {
    // 1. Update tab buttons
    document.querySelectorAll('.guide-tab-btn').forEach(btn => btn.classList.remove('active'));
    const targetBtn = document.getElementById('tabBtn-' + tabName);
    if (targetBtn) targetBtn.classList.add('active');

    // 2. Update tab panes
    document.querySelectorAll('.guide-tab-pane').forEach(pane => pane.classList.add('hidden'));
    const targetPane = document.getElementById('guideTabContent-' + tabName);
    if (targetPane) targetPane.classList.remove('hidden');

    // Reset scroll position on tab switch
    const contentBody = document.getElementById('guideContentBody');
    if (contentBody) contentBody.scrollTop = 0;
}

function selectGuideStep(stepNum) {
    // 1. Highlight active step card
    document.querySelectorAll('.guide-step-card').forEach(card => card.classList.remove('active'));
    const activeCard = document.getElementById('stepCard-' + stepNum);
    if (activeCard) activeCard.classList.add('active');

    // 2. Show corresponding step detail view
    document.querySelectorAll('.guide-step-detail').forEach(detail => detail.classList.add('hidden'));
    const activeDetail = document.getElementById('stepDetail-' + stepNum);
    if (activeDetail) activeDetail.classList.remove('hidden');
}

/**
 * Image Lightbox Viewer
 */
function openGuideLightbox(imgSrc, captionText) {
    const lightbox = document.getElementById('guideLightbox');
    const img = document.getElementById('guideLightboxImg');
    const cap = document.getElementById('guideLightboxCaption');
    if (!lightbox || !img) return;

    img.src = imgSrc;
    if (cap) cap.textContent = captionText || '';

    if (typeof lightbox.showModal === 'function') {
        lightbox.showModal();
    } else {
        lightbox.setAttribute('open', '');
    }
}

function closeGuideLightbox() {
    const lightbox = document.getElementById('guideLightbox');
    if (!lightbox) return;

    if (typeof lightbox.close === 'function') {
        lightbox.close();
    } else {
        lightbox.removeAttribute('open');
    }
}

// Close on backdrop click & restore scroll on close
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('userGuideModal');
    if (modal) {
        modal.addEventListener('close', () => {
            document.body.style.overflow = '';
        });
        modal.addEventListener('click', (e) => {
            const rect = modal.getBoundingClientRect();
            const isInDialog = (
                rect.top <= e.clientY &&
                e.clientY <= rect.top + rect.height &&
                rect.left <= e.clientX &&
                e.clientX <= rect.left + rect.width
            );
            if (!isInDialog) {
                closeUserGuideModal();
            }
        });
    }

    const lightbox = document.getElementById('guideLightbox');
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            const rect = lightbox.getBoundingClientRect();
            const isInDialog = (
                rect.top <= e.clientY &&
                e.clientY <= rect.top + rect.height &&
                rect.left <= e.clientX &&
                e.clientX <= rect.left + rect.width
            );
            if (!isInDialog) {
                closeGuideLightbox();
            }
        });
    }
});
</script>
