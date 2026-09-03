from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from omr_scanner import OMRScanner
import time
import fitz  # PyMuPDF

app = FastAPI(title="MSU OMR Scanning API", version="1.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/")
def read_root():
    return {"status": "online", "service": "MSU Scoring OMR Python Engine"}

@app.post("/scan")
async def scan_sheet(
    file: UploadFile = File(...),
    q_count: int = Form(50)
):
    start_time = time.time()
    contents = await file.read()
    
    if not contents:
        raise HTTPException(status_code=400, detail="Uploaded file is empty")

    scanner = OMRScanner(q_count=q_count)
    result = scanner.process(contents)
    
    elapsed_ms = int((time.time() - start_time) * 1000)
    result["process_time_ms"] = elapsed_ms

    return result

@app.post("/scan_pdf")
async def scan_pdf(
    file: UploadFile = File(...),
    q_count: int = Form(50),
    max_pages: int = Form(200)
):
    """
    Accept a multi-page PDF, extract each page as an image,
    run OMR scan on each, and return batch results.
    """
    pdf_bytes = await file.read()
    
    try:
        doc = fitz.open(stream=pdf_bytes, filetype="pdf")
    except Exception as e:
        return {"status": "error", "message": f"ไม่สามารถเปิดไฟล์ PDF ได้: {str(e)}"}
    
    total_pages = min(len(doc), max_pages)
    results = []
    
    for page_num in range(total_pages):
        page = doc[page_num]
        # Render at 200 DPI for good OCR quality
        mat = fitz.Matrix(200/72, 200/72)
        pix = page.get_pixmap(matrix=mat, colorspace=fitz.csRGB)
        img_bytes = pix.tobytes("jpeg")
        
        try:
            # Use the existing OMR scanner
            scanner = OMRScanner(q_count=q_count)
            scan_result = scanner.process(img_bytes)
            scan_result["page"] = page_num + 1
            results.append(scan_result)
        except Exception as e:
            results.append({
                "status": "error",
                "message": str(e),
                "page": page_num + 1
            })
    
    doc.close()
    
    success_count = sum(1 for r in results if r.get("status") == "success")
    
    return {
        "status": "success",
        "total_pages": total_pages,
        "success_count": success_count,
        "failed_count": total_pages - success_count,
        "results": results
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8000)
