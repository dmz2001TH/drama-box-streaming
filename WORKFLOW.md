# 🔄 Workflow — Agent ↔ Mimo Collaboration

> วิธีการทำงานร่วมกันระหว่าง Agent ตัวอื่นกับ Mimo

---

## Flow หลัก

```
┌─────────────┐
│   คุณสั่งงาน   │
└──────┬──────┘
       ↓
┌─────────────────────────────────────────────────┐
│              Agent ทำงาน                          │
│                                                   │
│  1. รับงาน → วิเคราะห์ → วางแผน                    │
│  2. ลงมือทำ (code, scrape, build)                  │
│  3. ติดปัญหา? ──── ไม่ติด → ทำต่อ → เสร็จ        │
│     │                                              │
│     ↓                                              │
│  4. ถาม Mimo ←─────────────────────────────┐      │
│     │                                      │      │
│     ↓                                      │      │
│  5. ได้คำตอบ → เอาไปแก้ไข                  │      │
│     │                                      │      │
│     └──── ยังไม่เสร็จ? ── กลับไปข้อ 2 ──────┘      │
│                                                   │
└─────────────────────────────────────────────────┘
       ↓
┌─────────────┐
│   ส่งงานให้คุณ   │
└─────────────┘
```

---

## เมื่อไหร่ Agent ควรถาม Mimo

### ✅ ควรถาม

| สถานการณ์ | ตัวอย่าง |
|-----------|---------|
| **ติด error ที่แก้ไม่ได้** | API ส่ง 403, code crash, ไม่เข้าใจ error |
| **ต้องการ reverse-engineer** | ไม่รู้ API endpoint, หา hidden API |
| **ต้องการ review** | เขียน code เสร็จแล้ว อยากให้ตรวจ |
| **ต้องการ design decision** | ไม่รู้จะใช้ approach ไหน |
| **ต้องการข้อมูลเพิ่ม** | หาข้อมูลบนเว็บ, ค้นหา API docs |
| **ต้องการ debug** | code รันได้แต่ผลลัพธ์ไม่ถูก |

### ❌ ไม่ต้องถาม

| สถานการณ์ | เหตุผล |
|-----------|--------|
| ทำตาม step ที่ชัดเจน | ไม่ต้องถาม ทำเลย |
| error ง่าย ๆ | syntax error, typo → แก้เอง |
| เสร็จแล้ว | ส่งงานได้เลย |

---

## Format การถาม

Agent ตัวอื่นถาม Mimo แบบนี้:

```
ทำงาน: [ชื่อ task]
สถานะ: [กำลังทำอะไร / ติดตรงไหน]
ปัญหา: [error message / สิ่งที่ไม่เข้าใจ]
สิ่งที่ลองแล้ว: [ลองอะไรไปบ้าง]
ต้องการ: [ให้ช่วยอะไร]
```

**ตัวอย่าง:**

```
ทำงาน: สร้าง API proxy สำหรับ DramaBox
สถานะ: เขียน server.js เสร็จแล้ว แต่ video ไม่เล่น
ปัญหา: sapi.dramaboxdb.com ส่ง 403 Access Denied
สิ่งที่ลองแล้ว: curl ตรง, เปลี่ยน User-Agent, เพิ่ม headers
ต้องการ: หาทาง bypass Akamai CDN block
```

---

## Mimo ตอบแบบไหน

### 1. ให้ code พร้อมใช้
```
ใช้ webfic.com API แทน:
POST https://www.webfic.com/webfic/home/browse
Headers: pline: DRAMABOX, language: th
Body: {"typeTwoId": 0, "pageNo": 1, "pageSize": 20}
```

### 2. ให้ step-by-step instructions
```
1. ติดตั้ง npm package: npm install @zhadev/dramabox
2. ลองเรียก scraper.getLatest(1)
3. ถ้าได้ 403 → ใช้ webfic API แทน (ดู code ด้านล่าง)
```

### 3. ให้ debug help
```
error 403 หมายถึง IP ถูกบล็อค (Akamai CDN)
ทางแก้: ใช้ residential proxy หรือใช้ webfic.com API แทน
```

### 4. Review + แนะนำ
```
code ใช้ได้ แต่แนะนำ:
1. เพิ่ม error handling สำหรับ timeout
2. cache response ไว้ 5 นาที
3. ใช้ try-catch รอบ fetch
```

---

## ตัวอย่าง Workflow จริง

### Task: "สร้างเว็บดูหนัง DramaBox"

```
Round 1:
  Agent: "อยากสร้างเว็บดูหนัง DramaBox ช่วยหา API หน่อย"
  Mimo:  "webfic.com มี API ใช้ได้ POST /webfic/home/browse
          ต้องส่ง header pline: DRAMABOX"

Round 2:
  Agent: "เขียน server.js proxy เสร็จแล้ว ลองรันดู"
  Mimo:  "ลองเรียก API ดูว่าได้ response ถูกมั้ย"

Round 3:
  Agent: "API ใช้ได้ แต่ video ไม่เล่น"
  Mimo:  "video URL ต้องผ่าน otp24hr proxy
          ลองดึงจาก <video> element บนหน้า player"

Round 4:
  Agent: "เจอ video URL แล้ว! MP4 1080p"
  Mimo:  "ดีมาก สร้าง HTML5 video player ได้เลย"

Round 5:
  Agent: "app เสร็จแล้ว ช่วย review หน่อย"
  Mimo:  "ใช้ได้ แนะนำเพิ่ม: error handling, loading states, mobile responsive"

Round 6:
  Agent: "แก้ตามที่แนะนำแล้ว ส่งงาน!"
  → เสร็จ ✅
```

---

## Rule หลัก

1. **Agent ทำเองได้ → ทำเลย** ไม่ต้องถาม
2. **ติด/ไม่แน่ใจ → ถาม Mimo** ด้วย format ข้างบน
3. **ได้คำตอบ → ทำต่อ** ไม่ต้องรอ confirm
4. **วน loop จนเสร็จ** → ส่งงาน
5. **Mimo ไม่ทำแทน** → ให้คำแนะนำ + code ตัวอย่าง แต่ Agent ต้องทำเอง

---

*ใช้ workflow นี้กับ agent ตัวอื่นได้ทุกตัว 🫡*
