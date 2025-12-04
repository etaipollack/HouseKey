# Raspberry Pi Wi-Fi Motor Controller

Control a 12 V DC motor over Wi-Fi using a Raspberry Pi and an L298N motor driver.
This project exposes a simple REST API using Python and Flask that accepts JSON POST requests to move the motor by a configurable amount of “steps,” supporting speed and direction control.

This is designed for IoT automation, robotics, access control, and remote physical actions triggered from a website or networked software.

---

## ✨ Features

* Wi-Fi controlled DC motor
* REST API (`/move` and `/health`)
* Step-based movement (time-emulated)
* PWM speed control (0–100%)
* Direction control (`cw` / `ccw`)
* Queueing + thread-safe commands
* Background execution with `nohup` or systemd
* Easily controlled from curl, fetch, or Python

---

## 🧰 Hardware Requirements

* **Raspberry Pi Zero W** (or any Pi with Wi-Fi)
* **L298N motor driver**
* **12 V DC motor**
* **12 V power supply**
* **Jumper wires**
* Shared **ground** between Pi and motor driver

---

## 🪛 Wiring

### Raspberry Pi (BCM) → L298N

| Pi GPIO | L298N Pin |
| ------: | :-------- |
|  GPIO17 | IN1       |
|  GPIO27 | IN2       |
|  GPIO22 | ENA       |
|     GND | GND       |

### Motor → L298N

* Motor leads → **OUT1** and **OUT2**

### Power → L298N

* 12 V DC **+** → L298N **+12V**
* 12 V DC **–** → L298N **GND**

> **Important:** Connect **Pi GND** to **L298N GND**.

---

## 🧪 API

### `GET /health`

Health check.

```bash
curl http://<PI_IP>:8080/health
```

**Response:**

```json
{"ok": true}
```

---

### `POST /move`

Move the motor by sending JSON.

#### JSON Body

```json
{
  "steps": 200,
  "dir": "cw",
  "speed": 80
}
```

#### Fields

| Field   | Type   | Required | Description                        |
| ------- | ------ | :------: | ---------------------------------- |
| `steps` | int    |    yes   | Number of step units               |
| `dir`   | string |    no    | `"cw"` or `"ccw"` (default `"cw"`) |
| `speed` | int    |    no    | 0–100 PWM (default 80)             |

#### Example command

```bash
curl -X POST http://<PI_IP>:8080/move \
  -H "Content-Type: application/json" \
  -d '{"steps": 200, "dir": "cw", "speed": 80}'
```

---

## ⚙️ Installation (Raspberry Pi)

### 1. Install system packages

```bash
sudo apt update
sudo apt install -y python3-flask python3-rpi.gpio
```

### 2. Clone repository

```bash
git clone https://github.com/<your-username>/<repo-name>.git
cd <repo-name>
```

### 3. Run server

```bash
sudo python3 motor_server.py
```

Server will listen on:

```
http://<PI_IP>:8080
```

---

## 🔁 Background Execution (easy method)

Use `nohup` so the server keeps running after SSH logout:

```bash
nohup sudo python3 motor_server.py > motor.log 2>&1 &
```

Check:

```bash
curl http://<PI_IP>:8080/health
```

Stop:

```bash
pkill -f motor_server.py
```

---

## ⚙️ Calibration (`SECONDS_PER_STEP`)

Inside `motor_server.py`:

```python
SECONDS_PER_STEP = 0.001
```

This controls how long each “step” lasts.

**Examples:**

* `200 steps` → 0.2 s
* `1000 steps` → 1.0 s

Tune this value to match the desired movement of your mechanism.

---

## 🔐 Security (recommended)

If port-forwarding is used, add:

* API key / Bearer token header
* Allowed IP list
* HTTPS via Cloudflare Tunnel
* Authentication middleware

Right now, **anyone who discovers the URL can move your motor** — secure accordingly.

---

## 🧭 Roadmap

* Limit switch / end-stop sensors
* Web control panel (HTML + JS)
* MQTT support
* API key authentication
* Containerization (Docker)
* OTA updates via GitHub

---

## 📦 Example Clients

### JavaScript

```js
fetch("http://<PI_IP>:8080/move", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ steps: 200, dir: "cw", speed: 80 })
});
```

### Python

```python
import requests
requests.post("http://<PI_IP>:8080/move", json={
  "steps": 200,
  "dir": "cw",
  "speed": 80
})
```

---

## 📄 License

MIT License

---

## 🙌 Author

**Etai Pollack**
