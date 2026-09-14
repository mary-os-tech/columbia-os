# 🗽 Columbia OS: Real-Time Immersive Simulation

![Status](https://img.shields.io/badge/Status-In%20Development-blue)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4)
![License](https://img.shields.io/badge/License-MIT-green)

> ⚠️ This project runs locally via XAMPP.
> No live demo is hosted — clone and run instructions are in the section below.
> Active development: currently on **Phase 5 of 50.**

An interactive ecosystem simulating university life in NYC, blending immersive 
storytelling, generative AI, and real-world hardware integration.

---

## 📖 About the Project

Columbia OS is more than a simulator. It is a proof of concept of how technology 
can create emotional bridges between the digital and the physical world.

The system operates as a "fake Operating System" within the browser, recreating 
the experience of sharing a dorm room at John Jay Hall with a Psychology student 
(an autonomous AI-driven NPC). Interaction happens through fake social media 
(Twitter Clone), messaging apps (iMessage Fake), and integrations that affect 
the user's real computer.

---

## 🧠 AI & Autonomy

The main NPC (Lottie) does not follow a fixed script. She features:

- **Function Calling:** Autonomous ability to like posts, send DMs, and post 
  subtle hints on secondary accounts (Alt accounts).
- **Long-Term Memory:** The database stores the player's interactions and 
  decisions, creating fights or sweet moments weeks later.
- **Reaction to Real-World Stimuli:** The AI reads the player's mood through 
  the music they are listening to on Spotify (Vibe Check).

---

## 🏗️ Architecture Overview

Columbia OS follows a simplified MVC pattern structured around three layers:

```
/actions/    → PHP endpoints handling all user and AI-triggered events
/pages/      → Frontend views (PHP-rendered)
/includes/   → Shared logic: AI engine, Spotify auth, DB connection, NPC config
/assets/     → JS modules and CSS themes
```

AI decisions flow through `includes/ai_engine.php`, which sends context 
(recent interactions, current mood state, memory weight) to the LLM and maps 
the response to executable functions (`like_tweet()`, `send_dm()`, 
`post_on_alt()`).

Real-time feel is achieved without WebSockets — through AJAX polling on 
`/actions/fetch_dms.php` and `/actions/fetch_timeline.php` at 3-second intervals.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP (Simplified MVC), MySQL |
| Frontend | HTML5, CSS3, JavaScript (Async AJAX with real-time polling) |
| AI | OpenAI / Claude API (Psychology System Prompt with text generation and autonomous decisions) |
| Real Integrations | Spotify API (reading current music and creating playlists), Google Calendar API, OpenWeatherMap |
| Hardware & IoT | Integration via PHP with OpenRGB (LEDs), WebOS API (LG TV), Web Speech API (Voice commands), CPU and Desktop File reading |

---

## ✨ Key Features

- 🌐 **Social Ecosystem (Twitter Clone):** Full timeline, Bookmarks, secret Alt 
  accounts, and the anxiety-inducing "deleted tweet" mechanic.
- ⏳ **"Touchdown" Transition:** The game dynamically switches time zones and 
  themes upon detecting the user has landed in NYC.
- 👻 **PC Interaction:** The NPC can: lower your real volume, lock your screen, 
  write .txt files on your real Desktop, or replace your "Ctrl+V" with 
  passive-aggressive notes.
- 💸 **University Economy:** "Venmo" for splitting bills, a programming 
  Freelancer system to earn in-game money, and a fake Amazon to buy PC parts.
- 🎮 **Co-Op Games:** Lottie's bot in real games (Minecraft via Mineflayer, 
  CS:GO, and internal PC-building mini-games).

---

## 📸 Gallery & Demos

### 🎬 Main Demo (35s)
▶️ [Click here to watch the main demo](https://youtu.be/J8caI9kaCE4)
*Login, AI autonomous chat, and Spotify integration.*

Or download the video here: [Download the .mp4](assets/videos/demo.mp4)

### 🎬 Bonus Demo (16s)
▶️ [Click here to watch the bonus demo](https://youtu.be/BN_hf6PT8-E)
Autonomous social content generation via DM command.

Or download the video here: [Download the .mp4](assets/videos/bonus.mp4)

### Screenshots

**Login Screen**
![Login screen — Columbia OS](assets/images/login.png)  
*Login screen — Columbia OS*

**Chat with Lottie**
![Natural conversation with the AI-driven NPC](assets/images/chat_lottie.png)  
*Natural conversation with the AI-driven NPC*

**Timeline & Spotify Player**
![Timeline with integrated Spotify player](assets/images/timeline.png)  
*Timeline with integrated Spotify player* 

**Spotify Reaction**
![Lottie reacting to your music in real-time via API](assets/images/reaction_spotify.png)  
*Lottie reacting to your music in real-time via API*

---

## 📌 Active Roadmap

The project is structured in **50 development phases.**
Currently on **Phase 5.**

### ✅ Completed

- **Phase 1 — Social Ecosystem:** Twitter clone with timeline, bookmarks, DMs, 
  read receipts, typing indicators, and deleted tweet mechanic. All built with 
  PHP endpoints and real-time AJAX polling.
- **Phase 2 — Environment System:** Timezone detection, "Touchdown" transition 
  (SP → NYC), dynamic UI themes, and live weather integration via 
  OpenWeatherMap.
- **Phase 3 — AI Awakening:** LLM integration via OpenRouter, Psychology-based 
  system prompt, autonomous posting via cron job, and full Function Calling 
  implementation.
- **Phase 4 — Academic & Economy Layer:** Canvas university portal, Venmo clone 
  for bill splitting, Freelancer terminal, battery system, and screen time 
  tracking.
- **Phase 5 — Spotify & Emotional Audio:** OAuth2 authentication, real-time mood 
  reading, collaborative playlists, Lottie's virtual player, Google Calendar 
  sync, and email reader (iFood/Uber Eats detection).

### 🔄 Next

- **Phase 6:** Gesture control via MediaPipe + hand recognition
- **Phase 7:** 3D holographic UI with Three.js floating apps
- **Phase 8:** 3D Avatar + realistic voice synthesis via ElevenLabs

### 🔭 Long-Term Vision

Full AR/VR integration, Kinect full-body tracking, Discord voice chat via 
ElevenLabs, and a complete life simulation arc from university through adulthood.

*Full roadmap available on request.*

---

## 🚀 How to Run Locally (XAMPP)

**1.** Clone the repository into your `htdocs` folder:

```bash
git clone https://github.com/mary-os-tech/columbia-os.git
```

**2.** Import the `columbia_os.sql` file into your phpMyAdmin to create 
the tables.

**3.** Create a `.env` file in the root folder and insert your API keys 
*(see API Keys Setup below).*

**4.** Configure XAMPP to use port 8080 (required for Spotify API):
- Open XAMPP Control Panel
- Click **Config** next to Apache
- Select **httpd.conf**
- Find the line: `Listen 80`
- Change it to: `Listen 8080`
- Save and restart Apache

**5.** Access `http://127.0.0.1:8080/columbia-os` in your browser.

---

## 🗄️ Database Setup

1. Open phpMyAdmin in your XAMPP control panel
2. Create a new database named `columbia_os`
3. Locate the file `columbia_os.sql` in your project folder
4. Import it using the **Import** tab in phpMyAdmin
5. All required tables will be created automatically

---

## 🔑 API Keys Setup

Create a `.env` file in the root of the project with the following keys:

### 1. OpenRouter AI *(for Lottie's personality)*

Go to [OpenRouter.ai](https://openrouter.ai), create a free account, and 
generate an API key.

```env
OPENROUTER_API_KEY=your_key_here
```

> 🔧 **Changing the model:** The system uses `deepseek/deepseek-chat` by default 
> (cost-efficient and well-suited for character simulation). To change it, edit 
> the `OPENROUTER_MODEL` constant inside `includes/ai_config.php`.
>
> 🆓 **Free alternatives:** Commented-out configurations for Groq and DeepSeek 
> are available inside `includes/ai_config.php`. Uncomment and add the 
> respective key to activate.

### 2. Spotify API *(for music integration)*

Go to the [Spotify Developer Dashboard](https://developer.spotify.com/dashboard), 
create an app, and set the Redirect URI to exactly:

```
http://127.0.0.1:8080/Columbia-os/includes/spotify_auth.php
```

```env
SPOTIFY_CLIENT_ID=your_id_here
SPOTIFY_CLIENT_SECRET=your_secret_here
```

### 3. OpenWeatherMap API *(for dynamic weather)*

Go to [OpenWeatherMap](https://openweathermap.org), create a free account, 
and generate an API key.

```env
OPENWEATHER_API_KEY=your_key_here
```

---

## 📫 Contact

- **GitHub:** [@mary-os-tech](https://github.com/mary-os-tech)
- **LinkedIn:** [www.linkedin.com/in/mariana-claumann-bb3b89429]
