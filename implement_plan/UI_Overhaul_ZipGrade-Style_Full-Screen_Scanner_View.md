# UI Overhaul: ZipGrade-Style Full-Screen Scanner View

**Target:** `scanner.php` and related JS/CSS.
**Goal:** ปรับปรุง UI ของหน้าสแกนกระดาษคำตอบ ให้มี UX เหมือนแอปสแกนเนอร์ Native (เช่น ZipGrade) โดยเน้นภาพกล้องแบบเต็มจอและใช้ UI แบบลอยทับ (Overlay) พร้อมคงธีมสี ม.มหาสารคาม (Yellow/Gray)

**Layout & Code Requirements:**

**1. Full-screen Camera Viewport (CRITICAL for OpenCV):**
* ปรับ Container หลักให้เป็น `w-screen h-[100dvh]` ปิดการเลื่อนหน้าจอด้วย `overflow-hidden` และพื้นหลังสีดำ (`bg-black`)
* ตัว `<video>` และ `<canvas>` ให้ใช้ `position: absolute` คลุมเต็มพื้นที่ 
* ⚠️ **ห้ามใช้ `object-fit: cover` เด็ดขาด เพราะจะตัดขอบภาพ OpenCV** ให้บังคับใช้ `object-fit: contain` หรือจัดการให้ภาพกล้องแสดงครบ 100% เสมอ แต่อยู่กึ่งกลางจอ (เลเยอร์ล่างสุด `z-0`)

**2. Viewfinders (กรอบเล็ง 4 มุม):**
* สร้าง Overlay วางทับบนกล้อง (`z-10`, `pointer-events-none`)
* ออกแบบเป็นกรอบสี่เหลี่ยมโปร่งใส สัดส่วน A4 มีเฉพาะเส้นขอบ (Border) หนา 2px-3px วางไว้ที่ 4 มุม (คล้ายกรอบเล็งในแอปกล้อง) สีขอบให้ใช้สีเหลือง (`border-yellow-500`) หรือสีขาวสว่างให้เห็นชัด

**3. Actionable Guidance Overlay:**
* สร้างกล่องข้อความกึ่งโปร่งใส ลอยอยู่ตรงกลางส่วนบนของหน้าจอ 
* ดีไซน์: `bg-black/60 backdrop-blur` หรือ `bg-gray-800/80`
* ข้อความ: "เล็งกรอบสี่เหลี่ยมบนกระดาษให้ตรงกับมุมทั้ง 4 ด้าน" (Text ขาว)

**4. Top & Bottom HUD Controls (Overlay):**
* **Top Bar (`absolute top-0 w-full z-20`):** โปร่งใส จัดแบบ `flex justify-between`. ซ้ายคือปุ่ม "< กลับ", ขวาคือปุ่ม "เลือกชุดข้อสอบ".
* **Bottom Bar (`absolute bottom-0 w-full z-20 pb-6`):** - จัดปุ่ม "สแกนนิสิต/สแกนเฉลย" เป็น Segmented Toggle เล็กๆ ตรงกลาง (Active ใช้สี `bg-yellow-500 text-gray-900`).
  - ปุ่ม "กรอกคะแนนด้วยตนเอง" ให้เป็นปุ่มขอบ (Outlined) หรือ Text Button ลอยอยู่ล่างสุด เพื่อไม่ให้กินพื้นที่กล้อง

**Output:** Provide the completely revised HTML/Tailwind structure for `scanner.php` following these exact instructions.