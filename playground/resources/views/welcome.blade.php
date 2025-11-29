<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Pragmatic Toolkit - Playground</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 48px;
        }

        h1 {
            font-size: 2.5rem;
            color: #2d3748;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .subtitle {
            color: #718096;
            font-size: 1.125rem;
            margin-bottom: 32px;
        }

        .description {
            color: #4a5568;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .feature {
            padding: 20px;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .feature h3 {
            color: #2d3748;
            font-size: 1rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .feature p {
            color: #718096;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        .footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            color: #a0aec0;
            font-size: 0.875rem;
            text-align: center;
        }

        @media (max-width: 640px) {
            .container {
                padding: 32px 24px;
            }

            h1 {
                font-size: 2rem;
            }

            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Laravel Pragmatic Toolkit</h1>
        <p class="subtitle">Lightweight CQRS, State Machines & Utilities</p>

        <p class="description">
            A pragmatic approach to Laravel development. This package provides lightweight CQRS pattern implementation,
            state machines, and additional utilities without the overhead of traditional DDD architectures.
        </p>

        <div class="features">
            <div class="feature">
                <h3>Query & Command</h3>
                <p>Source-agnostic read and write operations</p>
            </div>
            <div class="feature">
                <h3>Actions</h3>
                <p>Orchestrate business logic with pipelines</p>
            </div>
            <div class="feature">
                <h3>State Machines</h3>
                <p>Manage domain model states and workflows</p>
            </div>
            <div class="feature">
                <h3>Lightweight DTOs</h3>
                <p>Reflection-based data transformation</p>
            </div>
        </div>

        <div class="actions">
            <a href="/sandbox" class="btn btn-primary">Open Sandbox</a>
            <a href="https://github.com/shvakin/laravel-pragmatic" class="btn btn-secondary">GitHub</a>
        </div>

        <div class="footer">
            Laravel {{ app()->version() }} | PHP {{ PHP_VERSION }} | Environment: {{ config('app.env') }}
        </div>
    </div>
</body>
</html>
