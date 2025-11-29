# Sandbox Setup

The playground includes a sandbox environment for quick experiments and testing.

## First Time Setup

Copy the template files to create your working sandbox:

```bash
cd playground
cp app/Http/Controllers/SandboxController.php.template app/Http/Controllers/SandboxController.php
cp resources/views/sandbox.blade.php.template resources/views/sandbox.blade.php
```

## Usage

1. Open `/sandbox` route in your browser
2. Edit `app/Http/Controllers/SandboxController.php` to add your experimental code
3. Edit `resources/views/sandbox.blade.php` to customize the output
4. Your changes will NOT be committed to git

## How It Works

- Template files (`.template`) are tracked in git
- Working copies (without `.template`) are ignored by git
- You can experiment freely without affecting the repository
- To reset, simply copy the templates again
