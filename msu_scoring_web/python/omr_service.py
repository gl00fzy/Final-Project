from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from omr_scanner import OMRScanner
import time

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

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8000)
