# HANDOFF.md — DramaBox Streaming App

> อัปเดต: 2026-04-24 21:25 GMT+8
> Agent: OpenClaw main session (mimo-v2.5-pro)

---

## 🎯 โปรเจกต์คืออะไร

สร้างเว็บดูหนังสั้น DramaBox (short-form drama) โดยดึงข้อมูลจาก webfic.com API
ไม่ต้องเก็บหนังเอง — stream ตรงจาก CDN ของ DramaBox

---

## 🏗️ Flow การทำงาน

```
User → Browser → server.js (proxy) → webfic.com API → DramaBox backend
                                     ↓
                              JSON response
                                     ↓
                         server.js → Browser → render UI
```

**ไม่มี database** — ทุกอย่าง live จาก API

---

## ✅ สิ่งที่เสร็จแล้ว

### 1. Reverse-engineered APIs (webfic.com)

| Endpoint | Method | ใช้ทำอะไร | ใช้ได้ |
|----------|--------|----------|--------|
| `/webfic/home/browse` | POST | List + Search + Filter | ✅ |
| `/webfic/book/detail` | POST | รายละเอียดหนัง | ✅ |
| `/webfic/chapter/list` | POST | รายชื่อตอน (นิยาย) | ✅ |
| `/webfic/chapter/detail` | POST | เนื้อหาตอน | ✅ |
| `/webfic/book/search` | POST | ค้นหา (EN keyword) | ✅ |

**Headers ที่ต้องส่งทุกครั้ง:**
```
Content-Type: application/json
pline: DRAMABOX
language: th
```

**Body format:**
```json
// browse
{"typeTwoId": 0, "pageNo": 1, "pageSize": 20, "keyword": ""}

// detail
{"bookId": "42000010348", "language": "th"}

// search
{"keyword": "billionaire", "pageNo": 1, "pageSize": 20, "language": "th"}
```

### 2. Category IDs (typeTwoId)

| ID | หมวด |
|----|------|
| 683 | พากย์ไทย |
| 662 | ความรัก |
| 665 | ดราม่า |
| 663 | คอมเมดี้ |
| 685 | แต่งฟ้าผ่า |
| 667 | โรแมนติก |
| 671 | แก้แค้น |
| 701 | เกิดใหม่ |
| 673 | ข้ามมิติ |
| 654 | แฟนตาซี |
| 655 | แอคชั่น |
| 563 | Mafia |
| 682 | วาย |
| 664 | ครอบครัว |
| 658 | เทพเซียน |
| 661 | ย้อนยุค |
| 672 | รักขมขื่น |
| 656 | ชนบท |
| 669 | พีเรียด |

### 3. Response Structure

**browse response:**
```json
{
  "data": {
    "types": [{"id": 683, "name": "พากย์ไทย"}],
    "bookList": [{
      "bookId": "42000010348",
      "bookName": "เกิดใหม่เป็นสะใภ้ตัวแม่",
      "cover": "https://thwztchapter.dramaboxdb.com/.../42000010348.jpg@w=240&h=400",
      "chapterCount": 56,
      "viewCountDisplay": "18.0K"
    }]
  }
}
```

**detail response:**
```json
{
  "data": {
    "book": {
      "bookId": "42000010348",
      "bookName": "...",
      "cover": "...",
      "introduction": "เรื่องย่อ...",
      "chapterCount": 56,
      "labels": ["Billionaire", "Revenge"],
      "performerList": [
        {"performerId": "29479", "performerName": "Kim Han-young", "performerAvatar": "..."}
      ],
      "viewCountDisplay": "18.0K",
      "status": "PUBLISHED"
    }
  }
}
```

### 4. CDN URLs (ค้นพบ)

- **รูปปก:** `https://thwztchapter.dramaboxdb.com/data/cppartner/...`
- **รูปนักแสดง:** `https://hwztchapter.dramaboxdb.com/data/cppartner/...`
- **Video:** `https://hwztakavideo.dramaboxdb.com/.../xxx.1080p.nav2.mp4`

### 5. Code ที่สร้างแล้ว

| ไฟล์ | ขนาด | สถานะ |
|------|------|-------|
| `app.html` | 22KB | ✅ ใช้ได้ — dark theme, mobile-first, Thai UI |
| `server.js` | 2.8KB | ✅ ใช้ได้ — Node.js proxy (port 3000) |
| `index.php` | 1.7KB | ✅ ใช้ได้ — PHP proxy alternative |

### 6. สิ่งที่ app ทำได้แล้ว

- ✅ แสดงหนังเป็น grid cards
- ✅ 19 หมวดหมู่ (filter ได้)
- ✅ ค้นหา (browse + keyword)
- ✅ หน้า detail + เรื่องย่อ + นักแสดง
- ✅ รายชื่อตอน
- ✅ Pagination (load more)
- ✅ Dark theme UI
- ✅ Mobile-first responsive

---

## ⚠️ สิ่งที่ยังทำไม่ได้ / ต้องทำต่อ

### 1. Video Playback (ปัญหาหลัก)

**ปัญหา:** DramaBox video API (`sapi.dramaboxdb.com`) บล็อค IP จาก datacenter (Akamai CDN)
- Token generation ถูกบล็อค (403)
- `@zhadev/dramabox` npm package ก็ใช้ไม่ได้ (same issue)
- webfic API ไม่มี endpoint สำหรับ video URL

**ทางแก้ที่ลองได้:**
1. **Residential proxy** — ใช้ IP บ้าน/มือถือ เรียก `sapi.dramaboxdb.com`
2. **Mobile proxy** — ใช้ 4G/5G proxy
3. **ใช้ otp24hr.com** — เป็น source สำหรับ video URL (ต้อง session สด)
4. **Scrape จาก browser** — ใช้ Playwright/Puppeteer บน residential IP

**Video URL ตัวอย่างที่ดึงได้จริงจาก otp24hr:**
```
https://hwztakavideo.dramaboxdb.com/ce8f67b4150c21463ae30b3d6fde186b/69ecbad0/80/0x4/04x4/044x9/04490000024/700605008_1/700605008.1080p.nav2.mp4
```
→ MP4 1080p เล่นได้ตรง ไม่ต้อง auth แต่ token ใน URL มีอายุ

### 2. Rank/Trending API

- `/webfic/home/rank` → "频道不存在" (channel not found)
- ต้องหา channel ID ที่ถูกต้อง หรือใช้ browse + sortType แทน

### 3. Search ภาษาไทย

- `/webfic/book/search` ใช้ได้แต่ keyword ต้องเป็นภาษาอังกฤษ
- `/webfic/home/browse` + keyword ใช้ภาษาไทยได้ (แต่ results อาจไม่ตรง)

---

## 🔧 เทคนิคการหา API (ละเอียด)

### ขั้นตอนที่ 1: เริ่มจากเว็บเป้าหมาย

เข้า `https://www.otp24hr.com/drama` → เว็บนี้มีหนัง DramaBox ให้ดูฟรี
→ สงสัย: เขาเอามาจากไหน? ดึง API ยังไง?

### ขั้นตอนที่ 2: ดู Network Requests จาก Browser

ใช้ **browser tool** (Playwright) เปิดหน้าเว็บ แล้ว intercept network calls:

```javascript
// inject ใน browser console เพื่อดัก API calls
window._apiCalls = [];
const origFetch = window.fetch;
window.fetch = function(...args) {
  const url = typeof args[0] === 'string' ? args[0] : args[0]?.url;
  window._apiCalls.push({ url, opts: JSON.stringify(args[1] || {}) });
  return origFetch.apply(this, args);
};
```

→ เจอ API ของ otp24hr:
```
GET /api/v1/loadapi_series/api?endpoint=detail&category_p=dramabox&id=42000009440&lang=th
Headers: X-App-Key: T1RQMjRIUi1TRUNVUkUtQlJSQUJVUy05NXwyOTYxNzI2NA
         X-CSRF-Token: (session-based)
```

→ ปัญหา: ต้อง PHP session + Cloudflare token → เรียกจาก server ตรงไม่ได้

### ขั้นตอนที่ 3: หา Source ของ API

ค้นหา `sapi.dramaboxdb.com` → เจอว่าเป็น API หลักของ DramaBox
→ ลองเรียกตรง → **403 Access Denied** (Akamai CDN บล็อค datacenter IP)

### ขั้นตอนที่ 4: หา Open Source Scraper

ค้นหา `github dramabox api` → เจอ:

1. **`@zhadev/dramabox`** (npm package)
   - ติดตั้ง: `npm install @zhadev/dramabox`
   - มี RSA-SHA256 signing, token generation
   - แต่ token generation ถูกบล็อค (403 จาก Akamai)
   - **อ่าน source code** → เจอ base URLs, headers, signing logic

2. **`fahmih6/dramabox_player`** (Flutter app)
   - README บอกใช้ `api.sansekai.my.id`
   - → ลองเรียก → ปิดชั่วคราว (bandwidth หมด)

3. **Sansekai API Documentation**
   - `https://api.sansekai.my.id` → Swagger UI
   - มี endpoints: `/dramabox/foryou`, `/dramabox/latest`, `/dramabox/search`, `/dramabox/detail`
   - แต่ปิด public access แล้ว

### ขั้นตอนที่ 5: ค้นพบ webfic.com

จาก source code ของ `@zhadev/dramabox` เจอ:
```javascript
this.webficUrl = 'https://www.webfic.com';
// ใช้ endpoint: /webfic/home/browse, /webfic/book/detail/v2
```

→ ลองเรียก webfic.com API ตรง → **ใช้ได้!**

**กุญแจสำคัญ:** Header `pline: DRAMABOX`
- ไม่มี → ได้ novel content (bookIds: 21000xxx)
- มี → ได้ DramaBox video content (bookIds: 42000xxx)

### ขั้นตอนที่ 6: Brute-force หา Endpoints

ลองเรียก endpoint ต่าง ๆ ดูว่ามีอะไรใช้ได้บ้าง:

```javascript
// ลองทีละ endpoint
const endpoints = [
  '/webfic/home/browse',    // ✅ 200
  '/webfic/book/detail',    // ✅ 200 (ต้อง POST)
  '/webfic/book/search',    // ✅ 200
  '/webfic/chapter/list',   // ✅ 200 (empty สำหรับ video)
  '/webfic/chapter/detail', // ✅ 200 (ต้อง chapterId)
  '/webfic/home/rank',      // ❌ "频道不存在"
  '/webfic/book/trending',  // ❌ 404
  // ... ลอง 30+ endpoints
];
```

**วิธี brute-force:**
1. เดาชื่อ endpoint จาก pattern: `/webfic/{resource}/{action}`
2. ลอง GET ก่อน → ถ้า 405 → ลอง POST
3. ดู error message → บอก parameter ที่ต้องการ
4. ตัวอย่าง: `"参数非法 chapterId must not be null"` → รู้ว่าต้องส่ง chapterId

### ขั้นตอนที่ 7: หา Video URL จริง

กลับไป otp24hr → ใช้ browser intercept:

1. เปิดหน้า player (`/s/...`)
2. Inject fetch interceptor
3. คลิก "เล่นตอนที่ 1"
4. ดู `<video>` element → ได้ MP4 URL ตรง!

```javascript
// ดึง video URL จาก <video> element
const videos = document.querySelectorAll('video');
videos[0].src
// → "https://hwztakavideo.dramaboxdb.com/.../700605008.1080p.nav2.mp4"
```

5. ดู API calls ที่เกิดขึ้น:
```
GET /api/v1/loadapi_series/api?endpoint=video_dramabox&id=42000009440&chapterId=1
```
→ นี่คือ endpoint ที่ให้ video URL (ต้องผ่าน otp24hr proxy)

### ขั้นตอนที่ 8: ยืนยันว่า API ใช้ได้

ทดสอบทุก endpoint ผ่าน `curl`:

```bash
# Browse
curl -X POST https://www.webfic.com/webfic/home/browse \
  -H "Content-Type: application/json" \
  -H "pline: DRAMABOX" \
  -H "language: th" \
  -d '{"typeTwoId":0,"pageNo":1,"pageSize":5}'

# Detail
curl -X POST https://www.webfic.com/webfic/book/detail \
  -H "Content-Type: application/json" \
  -H "pline: DRAMABOX" \
  -H "language: th" \
  -d '{"bookId":"42000010348","language":"th"}'

# Search (EN keyword)
curl -X POST https://www.webfic.com/webfic/book/search \
  -H "Content-Type: application/json" \
  -H "pline: DRAMABOX" \
  -H "language: th" \
  -d '{"keyword":"billionaire","pageNo":1,"pageSize":5,"language":"th"}'
```

### เครื่องมือที่ใช้

| เครื่องมือ | ใช้ทำอะไร |
|-----------|----------|
| **Browser tool (Playwright)** | เปิดเว็บ, intercept network, ดู DOM |
| **fetch interceptor** | ดัก API calls จากหน้าเว็บ |
| **`<video>` element** | ดึง video URL ที่ player ใช้ |
| **curl** | ทดสอบ API endpoints ตรง |
| **Node.js** | ทดสอบ API จาก server |
| **npm package source** | อ่าน code เพื่อหา endpoints |
| **GitHub search** | หา open source scrapers |
| **web_fetch** | อ่าน README/docs |
| **Brute-force** | ลอง endpoint ทีละตัว |

### สรุป Reverse-Engineering Flow

```
1. เปิดเว็บเป้าหมาย
   ↓
2. ดู Network Requests (browser intercept)
   ↓
3. หา API base URL + auth method
   ↓
4. ลองเรียกตรง → ถ้าบล็อค → หาทางอ้อม
   ↓
5. ค้นหา open source (GitHub, npm)
   ↓
6. อ่าน source code → หา hidden endpoints
   ↓
7. Brute-force หา endpoints ที่ใช้ได้
   ↓
8. ยืนยันด้วย curl/Node.js
   ↓
9. สร้าง app ใช้ API ที่หาได้
```

### API Calling Pattern
```javascript
// ทุก request ต้องมี headers นี้
const headers = {
  'Content-Type': 'application/json',
  'pline': 'DRAMABOX',        // สำคัญ! ไม่งั้นได้ novel แทน video
  'language': 'th'
};

// POST ทุก endpoint
fetch('https://www.webfic.com/webfic/home/browse', {
  method: 'POST',
  headers,
  body: JSON.stringify({ typeTwoId: 0, pageNo: 1, pageSize: 20 })
});
```

### Architecture
```
drama-app/
├── app.html      ← Single-page app (HTML+CSS+JS, no framework)
├── server.js     ← Node.js proxy (bypasses CORS, port 3000)
└── index.php     ← PHP proxy alternative (for shared hosting)
```

- ไม่มี database — live API ทั้งหมด
- ไม่มี build tools — vanilla JS
- Dark theme, mobile-first
- CORS proxy ผ่าน server.js

### DramaBox API Architecture (ที่ reverse-engineer ได้)
```
webfic.com (frontend)
  └── /webfic/* (REST API)
       └── DramaBox backend (shared)
            ├── CDN: thwztchapter.dramaboxdb.com (images)
            ├── CDN: hwztakavideo.dramaboxdb.com (video)
            └── API: sapi.dramaboxdb.com (auth required, blocked from datacenter)
```

---

## 📋 Prompt สำหรับ Agent ตัวอื่น

```
คุณกำลังทำงานต่อโปรเจกต์ DramaBox Streaming App

📂 Location: /root/.openclaw/workspace/drama-app/
📄 Handoff: /root/.openclaw/workspace/HANDOFF.md

## สิ่งที่เสร็จแล้ว
- Reverse-engineer webfic.com API (browse, detail, search, chapters)
- สร้าง app.html (dark theme, mobile-first, Thai UI)
- สร้าง server.js (Node.js proxy, port 3000)
- สร้าง index.php (PHP proxy alternative)
- ค้นพบ CDN URLs (images + video)
- ระบุ 19 category IDs

## สิ่งที่ต้องทำต่อ
1. **Video Playback** — DramaBox video API บล็อค datacenter IP ต้องหา residential proxy หรือใช้ otp24hr.com เป็น source
2. **Rank/Trending** — หา channel ID ที่ถูกต้องสำหรับ /webfic/home/rank
3. **Search ภาษาไทย** — ปรับปรุง search ให้รองรับภาษาไทยดีขึ้น
4. **Deploy** — ขึ้น VPS/Cloud (ต้อง residential IP สำหรับ video)

## API ที่ใช้ได้
POST https://www.webfic.com/webfic/home/browse (list + search + filter)
POST https://www.webfic.com/webfic/book/detail (รายละเอียด)
POST https://www.webfic.com/book/search (ค้นหา EN)
Headers: pline: DRAMABOX, language: th

## วิธีรัน
cd /root/.openclaw/workspace/drama-app && node server.js
เปิด http://localhost:3000

อ่าน HANDOFF.md สำหรับข้อมูลเต็ม ๆ
```

---

## 🚀 วิธีรัน

```bash
cd /root/.openclaw/workspace/drama-app
node server.js
# เปิด http://localhost:3000
```

---

*ขอให้โชคดีครับ 🫡*
