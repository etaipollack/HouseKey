import time
import threading
import queue
from flask import Flask, request, jsonify
import RPi.GPIO as GPIO

# ----------------- CONFIG -----------------
# GPIO pins (BCM numbering)
IN1 = 17   # L298N IN1
IN2 = 27   # L298N IN2
ENA = 22   # L298N ENA (PWM)

PWM_FREQ = 1000       # Hz
DEFAULT_SPEED = 80    # % duty cycle
MAX_SPEED = 100       # cap speed at 100%

# "Step" calibration:
# seconds the motor runs per 1 "step"
# You will TUNE THIS VALUE by experiment.
SECONDS_PER_STEP = 0.001
# So 200 steps = 2.0 seconds at DEFAULT_SPEED
# ------------------------------------------


# Global state
app = Flask(__name__)
cmd_queue = queue.Queue()
motor_lock = threading.Lock()
running = True

# GPIO setup
GPIO.setmode(GPIO.BCM)
GPIO.setup(IN1, GPIO.OUT)
GPIO.setup(IN2, GPIO.OUT)
GPIO.setup(ENA, GPIO.OUT)

pwm = GPIO.PWM(ENA, PWM_FREQ)
pwm.start(0)  # motor initially off


def stop_motor():
    GPIO.output(IN1, GPIO.LOW)
    GPIO.output(IN2, GPIO.LOW)
    pwm.ChangeDutyCycle(0)


def run_motor_steps(steps, direction, speed=None):
    """
    Run motor for `steps` "units" in given direction.
    direction: "cw" or "ccw"
    speed: PWM duty (0-100)
    """
    if steps <= 0:
        return

    if speed is None:
        speed = DEFAULT_SPEED

    speed = max(0, min(int(speed), MAX_SPEED))

    duration = steps * SECONDS_PER_STEP

    if direction == "cw":
        GPIO.output(IN1, GPIO.HIGH)
        GPIO.output(IN2, GPIO.LOW)
    else:  # "ccw"
        GPIO.output(IN1, GPIO.LOW)
        GPIO.output(IN2, GPIO.HIGH)

    pwm.ChangeDutyCycle(speed)
    time.sleep(duration)
    stop_motor()


def worker():
    """Background thread: executes motor commands sequentially."""
    while running:
        try:
            cmd = cmd_queue.get(timeout=0.5)
        except queue.Empty:
            continue

        try:
            with motor_lock:
                if cmd["type"] == "move":
                    run_motor_steps(
                        steps=cmd["steps"],
                        direction=cmd["dir"],
                        speed=cmd.get("speed")
                    )
        finally:
            cmd_queue.task_done()


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"ok": True})


@app.route("/move", methods=["POST"])
def move():
    """
    POST /move
    JSON body:
    {
      "steps": 200,       # required, >0
      "dir": "cw|ccw",    # optional, default "cw"
      "speed": 80         # optional, 0-100 (%)
    }
    """
    if not request.is_json:
        return jsonify({"error": "expected JSON"}), 400

    data = request.get_json(silent=True) or {}

    try:
        steps = int(data.get("steps", 0))
    except (TypeError, ValueError):
        return jsonify({"error": "steps must be an integer"}), 400

    if steps <= 0:
        return jsonify({"error": "steps must be > 0"}), 400

    dir_str = data.get("dir", "cw")
    if dir_str not in ("cw", "ccw"):
        return jsonify({"error": "dir must be 'cw' or 'ccw'"}), 400

    speed = data.get("speed", None)
    if speed is not None:
        try:
            speed = int(speed)
        except (TypeError, ValueError):
            return jsonify({"error": "speed must be int 0-100"}), 400

    cmd_queue.put({
        "type": "move",
        "steps": steps,
        "dir": dir_str,
        "speed": speed
    })

    return jsonify({
        "status": "queued",
        "steps": steps,
        "dir": dir_str,
        "speed": speed if speed is not None else DEFAULT_SPEED
    }), 202


def main():
    # Start worker thread
    t = threading.Thread(target=worker, daemon=True)
    t.start()
    try:
        # 0.0.0.0 = listen on all interfaces, port 8080
        app.run(host="0.0.0.0", port=8080, threaded=True)
    finally:
        global running
        running = False
        t.join(timeout=1.0)
        stop_motor()
        pwm.stop()
        GPIO.cleanup()


if __name__ == "__main__":
    main()
