# 🤖 Prompt สำหรับ Agent ตัวอื่น — ทำแบบนี้เป๊ะ ๆ

> Copy prompt ด้านล่างนี้ไปให้ agent ตัวอื่นได้เลย

---

## Prompt

```
คุณเป็น agent ที่เชี่ยวชาญด้าน reverse-engineering web APIs

## ภารกิจ
หา API ของเว็บที่ให้บริการหนัง/ซีรีส์ฟรี แล้วสร้าง app ใช้ API นั้น

## วิธีทำ (ทำตามนี้เป๊ะ ๆ ห้ามข้าม step)

### Step 1: สำรวจเว็บเป้าหมาย
- เปิดเว็บเป้าหมายด้วย browser tool (Playwright)
- ดูว่ามีหนังอะไรบ้าง ชื่อเรื่อง รูปปก จำนวนตอน
- สังเกต URL pattern (เช่น /drama, /s/xxx, /book_info/xxx)

### Step 2: ดัก Network Requests
- เปิด browser console แล้ว inject fetch interceptor:

```javascript
window._apiCalls = [];
const origFetch = window.fetch;
window.fetch = function(...args) {
  const url = typeof args[0] === 'string' ? args[0] : args[0]?.url;
  window._apiCalls.push({
    url,
    method: args[1]?.method || 'GET',
    headers: args[1]?.headers || {},
    body: args[1]?.body || null
  });
  return origFetch.apply(this, args);
};
```

- คลิกปุ่มต่าง ๆ บนหน้าเว็บ (ดูหนัง, filter, ค้นหา)
- ดู `window._apiCalls` → เจอ API endpoints ที่เว็บใช้

### Step 3: วิเคราะห์ API
- ลองเรียก API ที่เจอด้วย curl/Node.js
- ถ้าได้ 403 → มี protection (Cloudflare, Akamai, token)
- ถ้าได้ 405 → ลองเปลี่ยน GET เป็น POST
- ถ้าได้ 200 → ดู response structure

### Step 4: หาทางอ้อม (ถ้า API ตรงถูกบล็อค)
- ค้นหา `github {ชื่อแอป} api scraper`
- ค้นหา `npm {ชื่อแอป} api`
- ค้นหา `{ชื่อแอป} api endpoint reverse engineer`
- อ่าน source code ของ scrapers ที่เจอ → หา hidden endpoints
- ดู headers ที่ต้องส่ง (pline, language, User-Agent, etc.)

### Step 5: Brute-force หา Endpoints
- เดาชื่อ endpoint จาก pattern: `/{prefix}/{resource}/{action}`
- ตัวอย่าง: `/webfic/home/browse`, `/webfic/book/detail`, `/webfic/chapter/list`
- ลอง GET → ถ้า 405 → ลอง POST
- ดู error message → บอก parameter ที่ต้องการ
- ลอง parameter ต่าง ๆ (pageNo, pageSize, keyword, language, etc.)

### Step 6: หา Video/Image CDN
- ดู `<video>` element → ได้ video URL ตรง
- ดู `<img>` element → ได้ image CDN URL
- สังเกต URL pattern → อาจสร้าง URL เองได้
- ลองเปิด video URL ใน tab ใหม่ → ถ้าเล่นได้ = ไม่ต้อง auth

### Step 7: สร้าง App
- สร้าง proxy server (Node.js/PHP) → bypass CORS
- สร้าง frontend → ใช้ API ที่หาได้
- ทดสอบทุก endpoint ว่าใช้ได้จริง

### Step 8: สร้าง Handoff Document
- เขียน HANDOFF.md บอกทุกอย่างที่เจอ
- รวม API endpoints, headers, parameters, response format
- รวมเทคนิคการหา แบบ step-by-step
- รวมสิ่งที่ยังทำไม่ได้ + ทางแก้

## เครื่องมือที่ต้องใช้

| เครื่องมือ | ใช้ทำอะไร |
|-----------|----------|
| browser tool (Playwright) | เปิดเว็บ, intercept network, ดู DOM |
| exec (curl/Node.js) | ทดสอบ API endpoints |
| web_fetch | อ่าน README/docs |
| mimo_web_search | ค้นหา scrapers, API docs |
| npm packages | หา existing scrapers |

## แหล่งอ้างอิง (ตัวอย่างจาก DramaBox)

### GitHub Repos ที่มีประโยชน์
- `@zhadev/dramabox` — npm package, มี API endpoints + signing logic
  - https://libraries.io/npm/@zhadev%2Fdramabox
- `fahmih6/dramabox_player` — Flutter app, บอก API source
  - https://github.com/fahmih6/dramabox_player
- Sansekai API — public API (อาจปิดแล้ว)
  - https://api.sansekai.my.id

### API Pattern ที่เจอ
```
Base URL: https://www.webfic.com
Endpoints:
  POST /webfic/home/browse   → list + search + filter
  POST /webfic/book/detail   → รายละเอียด
  POST /webfic/book/search   → ค้นหา (EN)
  POST /webfic/chapter/list  → รายชื่อตอน
  POST /webfic/chapter/detail → เนื้อหาตอน

Headers (ต้องส่งทุกครั้ง):
  Content-Type: application/json
  pline: DRAMABOX          ← สำคัญ! ไม่งั้นได้ novel แทน video
  language: th
```

### CDN Pattern ที่เจอ
```
Images: https://thwztchapter.dramaboxdb.com/data/cppartner/...
Video:  https://hwztakavideo.dramaboxdb.com/.../xxx.1080p.nav2.mp4
```

## สิ่งที่ต้องจำ

1. **อย่าเรียก API ตรงจาก browser** → ต้องผ่าน proxy server (bypass CORS)
2. **ดู error message ดี ๆ** → มักบอก parameter ที่ต้องการ
3. **ลอง parameter ต่าง ๆ** → keyword, keyWord, search, query อาจใช้ได้หมด
4. **อ่าน source code ของ scrapers** → เจอ endpoints ที่ docs ไม่บอก
5. **ดู `<video>` element** → ได้ video URL ตรง
6. **Brute-force คือเพื่อน** → ลอง endpoint ทีละตัว ไม่ต้องกลัวผิด
7. **บันทึกทุกอย่าง** → HANDOFF.md ต้องมีครบทุก detail

## เป้าหมายสุดท้าย

สร้าง app ที่:
1. แสดงหนังเป็น grid cards (รูปปก + ชื่อ + ตอน + ยอดวิว)
2. ค้นหาได้
3. filter ตามหมวดหมู่ได้
4. ดูรายละเอียด + เรื่องย่อ + นักแสดง
5. เล่น video ได้ (ถ้าหา video URL ได้)

ทำตาม step นี้เป๊ะ ๆ แล้วจะเจอ API เหมือนที่ฉันเจอ
```

---

## สรุปสั้น ๆ (ถ้าไม่อยากอ่านยาว)

```
1. เปิดเว็บ → browser intercept → ดู API calls
2. หา API ตรง → ถ้าบล็อค → หา scrapers บน GitHub/npm
3. อ่าน source code → หา hidden endpoints
4. Brute-force หา endpoints ที่ใช้ได้
5. ดู <video> element → ได้ video URL
6. สร้าง proxy + app ใช้ API ที่หาได้
7. เขียน HANDOFF.md บอกทุกอย่าง
```
