# Raspberry Pi Wi-Fi Motor Controller

Control a 12 V DC motor over Wi-Fi using a Raspberry Pi and an L298N motor driver.  
This project exposes a simple REST API using Python and Flask that accepts JSON POST requests to move the motor by a configurable amount of “steps,” supporting speed and direction control.

This is designed for IoT automation, robotics, access control, and remote physical actions triggered from a website or networked software.

---

## ✨ Features

- Wi-Fi controlled DC motor
- REST API (`/move` and `/health`)
- Step-based movement (time-emulated)
- PWM speed control (0–100%)
- Direction control (`cw` / `ccw`)
- Queueing + thread-safe commands
- Background execution with `nohup` or systemd
- Easily controlled from curl, fetch, or Python

---

## 🧰 Hardware Requirements

- **Raspberry Pi Zero W** (or any Pi with Wi-Fi)
- **L298N motor driver**
- **12 V DC motor**
- **12 V power supply**
- **Jumper wires**
- Shared **ground** between Pi and motor driver

---

## 🪛 Wiring

### Raspberry Pi (BCM) → L298N

| Pi GPIO | L298N Pin |
|--------:|:---------|
| GPIO17  | IN1      |
| GPIO27  | IN2      |
| GPIO22  | ENA      |
| GND     | GND      |

### Motor → L298N

- Motor leads → **OUT1** and **OUT2**

### Power → L298N

- 12 V DC **+** → L298N **+12V**
- 12 V DC **–** → L298N **GND**

> **Important:** Connect **Pi GND** to **L298N GND**.

---

## 🧪 API

### `GET /health`

Health check.

```bash
curl http://<PI_IP>:8080/health
