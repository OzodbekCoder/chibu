<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'AnimeStore') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                background: #0a0a0a;
                color: #ededec;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            header {
                width: 100%;
                max-width: 800px;
                padding: 1.5rem 1rem 0;
                display: flex;
                justify-content: flex-end;
                gap: 1rem;
            }
            header a {
                display: inline-block;
                padding: 0.375rem 1.25rem;
                border: 1px solid #3e3e3a;
                border-radius: 4px;
                font-size: 0.875rem;
                color: #ededec;
                text-decoration: none;
                transition: border-color 0.15s;
            }
            header a:hover { border-color: #62605b; }
            main {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
                gap: 1.5rem;
            }
            h1 {
                font-size: 1.5rem;
                font-weight: 600;
                letter-spacing: 0.05em;
                color: #4ade80;
                text-transform: uppercase;
            }
            #game-container {
                position: relative;
                border: 2px solid #3e3e3a;
                border-radius: 8px;
                overflow: hidden;
            }
            canvas {
                display: block;
                background: #111;
            }
            #overlay {
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background: rgba(0,0,0,0.75);
                gap: 1rem;
            }
            #overlay h2 {
                font-size: 2rem;
                font-weight: 700;
                color: #4ade80;
            }
            #overlay p {
                font-size: 0.9rem;
                color: #a1a09a;
            }
            #overlay .score-display {
                font-size: 1.1rem;
                color: #ededec;
            }
            #start-btn {
                padding: 0.6rem 2rem;
                background: #4ade80;
                color: #0a0a0a;
                border: none;
                border-radius: 6px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.15s;
            }
            #start-btn:hover { background: #22c55e; }
            #hud {
                display: flex;
                gap: 3rem;
                font-size: 0.9rem;
                color: #a1a09a;
            }
            #hud span { color: #4ade80; font-weight: 600; }
            #controls {
                font-size: 0.75rem;
                color: #706f6c;
                text-align: center;
                line-height: 1.8;
            }
        </style>
    </head>
    <body>
        <header>
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Register</a>
                    @endif
                @endauth
            @endif
        </header>

        <main>
            <h1>Snake</h1>

            <div id="hud">
                <div>Score: <span id="score">0</span></div>
                <div>Best: <span id="best">0</span></div>
                <div>Level: <span id="level">1</span></div>
            </div>

            <div id="game-container">
                <canvas id="canvas" width="400" height="400"></canvas>
                <div id="overlay">
                    <h2>SNAKE</h2>
                    <p>Ilon ovqat yeb o'sing!</p>
                    <button id="start-btn">Boshlash</button>
                    <p>WASD yoki O'Q tugmalari</p>
                </div>
            </div>

            <div id="controls">
                ↑ / W — Yuqori &nbsp;|&nbsp; ↓ / S — Pastga &nbsp;|&nbsp; ← / A — Chapga &nbsp;|&nbsp; → / D — O'ngga<br>
                P — Pauza
            </div>
        </main>

        <script>
        (function() {
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');
            const overlay = document.getElementById('overlay');
            const startBtn = document.getElementById('start-btn');
            const scoreEl = document.getElementById('score');
            const bestEl = document.getElementById('best');
            const levelEl = document.getElementById('level');

            const CELL = 20;
            const COLS = canvas.width / CELL;
            const ROWS = canvas.height / CELL;

            let snake, dir, nextDir, food, score, best, level, gameLoop, paused, gameOver;

            best = parseInt(localStorage.getItem('snake_best') || '0');
            bestEl.textContent = best;

            function init() {
                snake = [
                    { x: 10, y: 10 },
                    { x: 9,  y: 10 },
                    { x: 8,  y: 10 },
                ];
                dir = { x: 1, y: 0 };
                nextDir = { x: 1, y: 0 };
                score = 0;
                level = 1;
                paused = false;
                gameOver = false;
                scoreEl.textContent = 0;
                levelEl.textContent = 1;
                spawnFood();
            }

            function spawnFood() {
                let pos;
                do {
                    pos = {
                        x: Math.floor(Math.random() * COLS),
                        y: Math.floor(Math.random() * ROWS),
                    };
                } while (snake.some(s => s.x === pos.x && s.y === pos.y));
                food = pos;
            }

            function getSpeed() {
                return Math.max(60, 200 - (level - 1) * 15);
            }

            function step() {
                dir = { ...nextDir };
                const head = {
                    x: (snake[0].x + dir.x + COLS) % COLS,
                    y: (snake[0].y + dir.y + ROWS) % ROWS,
                };

                if (snake.some(s => s.x === head.x && s.y === head.y)) {
                    endGame();
                    return;
                }

                snake.unshift(head);

                if (head.x === food.x && head.y === food.y) {
                    score++;
                    scoreEl.textContent = score;
                    if (score > best) {
                        best = score;
                        bestEl.textContent = best;
                        localStorage.setItem('snake_best', best);
                    }
                    level = Math.floor(score / 5) + 1;
                    levelEl.textContent = level;
                    spawnFood();
                    clearInterval(gameLoop);
                    gameLoop = setInterval(step, getSpeed());
                } else {
                    snake.pop();
                }

                draw();
            }

            function draw() {
                ctx.fillStyle = '#111';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // grid dots
                ctx.fillStyle = '#1a1a1a';
                for (let x = 0; x < COLS; x++) {
                    for (let y = 0; y < ROWS; y++) {
                        ctx.fillRect(x * CELL + CELL/2 - 1, y * CELL + CELL/2 - 1, 2, 2);
                    }
                }

                // food
                const fx = food.x * CELL + CELL / 2;
                const fy = food.y * CELL + CELL / 2;
                const radius = CELL / 2 - 2;
                ctx.beginPath();
                ctx.arc(fx, fy, radius, 0, Math.PI * 2);
                ctx.fillStyle = '#f43';
                ctx.fill();
                ctx.beginPath();
                ctx.arc(fx - 2, fy - 2, radius * 0.3, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255,255,255,0.4)';
                ctx.fill();

                // snake
                snake.forEach((seg, i) => {
                    const t = i / snake.length;
                    const g = Math.round(174 - t * 80);
                    ctx.fillStyle = i === 0 ? '#4ade80' : `rgb(0, ${g}, 60)`;
                    const padding = i === 0 ? 1 : 2;
                    const size = CELL - padding * 2;
                    ctx.beginPath();
                    roundRect(ctx, seg.x * CELL + padding, seg.y * CELL + padding, size, size, 4);
                    ctx.fill();

                    if (i === 0) {
                        // eyes
                        const ex = seg.x * CELL + CELL / 2;
                        const ey = seg.y * CELL + CELL / 2;
                        const eyeOffsets = getEyeOffsets();
                        ctx.fillStyle = '#0a0a0a';
                        ctx.beginPath();
                        ctx.arc(ex + eyeOffsets[0].x, ey + eyeOffsets[0].y, 2, 0, Math.PI * 2);
                        ctx.fill();
                        ctx.beginPath();
                        ctx.arc(ex + eyeOffsets[1].x, ey + eyeOffsets[1].y, 2, 0, Math.PI * 2);
                        ctx.fill();
                    }
                });
            }

            function getEyeOffsets() {
                if (dir.x === 1)  return [{ x: 3, y: -3 }, { x: 3, y: 3 }];
                if (dir.x === -1) return [{ x: -3, y: -3 }, { x: -3, y: 3 }];
                if (dir.y === -1) return [{ x: -3, y: -3 }, { x: 3, y: -3 }];
                return [{ x: -3, y: 3 }, { x: 3, y: 3 }];
            }

            function roundRect(ctx, x, y, w, h, r) {
                ctx.moveTo(x + r, y);
                ctx.lineTo(x + w - r, y);
                ctx.arcTo(x + w, y, x + w, y + r, r);
                ctx.lineTo(x + w, y + h - r);
                ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
                ctx.lineTo(x + r, y + h);
                ctx.arcTo(x, y + h, x, y + h - r, r);
                ctx.lineTo(x, y + r);
                ctx.arcTo(x, y, x + r, y, r);
            }

            function endGame() {
                clearInterval(gameLoop);
                gameOver = true;
                overlay.style.display = 'flex';
                overlay.innerHTML = `
                    <h2 style="color:#f43">GAME OVER</h2>
                    <div class="score-display">Ball: ${score} &nbsp;|&nbsp; Darajа: ${level}</div>
                    <button id="start-btn" onclick="document.getElementById('start-btn').dispatchEvent(new Event('click'))">Qayta boshlash</button>
                `;
                document.getElementById('start-btn').addEventListener('click', startGame);
            }

            function startGame() {
                overlay.style.display = 'none';
                init();
                clearInterval(gameLoop);
                gameLoop = setInterval(step, getSpeed());
                draw();
            }

            document.addEventListener('keydown', e => {
                switch(e.key) {
                    case 'ArrowUp':    case 'w': case 'W':
                        if (dir.y !== 1)  nextDir = { x: 0, y: -1 }; break;
                    case 'ArrowDown':  case 's': case 'S':
                        if (dir.y !== -1) nextDir = { x: 0, y: 1 };  break;
                    case 'ArrowLeft':  case 'a': case 'A':
                        if (dir.x !== 1)  nextDir = { x: -1, y: 0 }; break;
                    case 'ArrowRight': case 'd': case 'D':
                        if (dir.x !== -1) nextDir = { x: 1, y: 0 };  break;
                    case 'p': case 'P':
                        if (!gameOver) {
                            paused = !paused;
                            if (paused) {
                                clearInterval(gameLoop);
                                ctx.fillStyle = 'rgba(0,0,0,0.5)';
                                ctx.fillRect(0, 0, canvas.width, canvas.height);
                                ctx.fillStyle = '#ededec';
                                ctx.font = '600 1.5rem "Instrument Sans", sans-serif';
                                ctx.textAlign = 'center';
                                ctx.fillText('PAUZA', canvas.width/2, canvas.height/2);
                            } else {
                                gameLoop = setInterval(step, getSpeed());
                            }
                        }
                        break;
                }
                if (['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                }
            });

            // Touch controls
            let touchStart = null;
            canvas.addEventListener('touchstart', e => {
                touchStart = { x: e.touches[0].clientX, y: e.touches[0].clientY };
                e.preventDefault();
            }, { passive: false });
            canvas.addEventListener('touchend', e => {
                if (!touchStart) return;
                const dx = e.changedTouches[0].clientX - touchStart.x;
                const dy = e.changedTouches[0].clientY - touchStart.y;
                if (Math.abs(dx) > Math.abs(dy)) {
                    if (dx > 20 && dir.x !== -1) nextDir = { x: 1, y: 0 };
                    if (dx < -20 && dir.x !== 1) nextDir = { x: -1, y: 0 };
                } else {
                    if (dy > 20 && dir.y !== -1) nextDir = { x: 0, y: 1 };
                    if (dy < -20 && dir.y !== 1) nextDir = { x: 0, y: -1 };
                }
                touchStart = null;
                e.preventDefault();
            }, { passive: false });

            startBtn.addEventListener('click', startGame);

            // draw initial preview
            init();
            draw();
        })();
        </script>
    </body>
</html>
