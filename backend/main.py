from fastapi import FastAPI, HTTPException, Request
from youtube_transcript_api import YouTubeTranscriptApi, TranscriptsDisabled, NoTranscriptFound
import openai
import os

app = FastAPI()

OPENAI_API_KEY = os.getenv("OPENAI_API_KEY")  # Set this in your VPS environment

@app.get("/transcript")
async def get_transcript(video_id: str):
    try:
        transcript = YouTubeTranscriptApi.get_transcript(video_id)
        text = " ".join([entry['text'] for entry in transcript])
        return {"transcript": text}
    except (TranscriptsDisabled, NoTranscriptFound):
        raise HTTPException(status_code=404, detail="No transcript available")
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/openai")
async def openai_proxy(request: Request):
    data = await request.json()
    prompt = data.get("prompt")
    if not prompt:
        raise HTTPException(status_code=400, detail="Prompt required")
    openai.api_key = OPENAI_API_KEY
    try:
        response = openai.ChatCompletion.create(
            model="gpt-3.5-turbo",
            messages=[{"role": "user", "content": prompt}],
            max_tokens=1024,
        )
        return {"result": response.choices[0].message["content"]}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))