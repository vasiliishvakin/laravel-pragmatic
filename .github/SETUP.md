# CI/CD Setup Instructions

This document describes the CI/CD setup and what you need to configure on GitHub.

## ✅ Already Configured

The following is already set up in the repository:

- ✅ GitHub Actions workflows (`.github/workflows/`)
- ✅ Dependabot configuration (`.github/dependabot.yml`)
- ✅ Codecov configuration (`codecov.yml`)
- ✅ Laravel Pint for code styling (`pint.json`)
- ✅ Composer scripts for testing and linting

## 🔧 GitHub Repository Setup

After pushing the repository to GitHub, you need to configure the following:

### 1. Codecov Integration

**Option A: Using GitHub App (Recommended)**

1. Go to https://codecov.io/
2. Sign in with your GitHub account
3. Click "Add new repository"
4. Select `vasiliishvakin/laravel-pragmatic`
5. Codecov will automatically create the integration
6. Copy the `CODECOV_TOKEN` from the repository settings

**Option B: Using Token**

1. Go to https://codecov.io/
2. Sign in with your GitHub account
3. Add the repository
4. Navigate to Settings → General → Repository Upload Token
5. Copy the token

**Add Token to GitHub Secrets:**

1. Go to your GitHub repository
2. Navigate to Settings → Secrets and variables → Actions
3. Click "New repository secret"
4. Name: `CODECOV_TOKEN`
5. Paste the token from Codecov
6. Click "Add secret"

### 2. GitHub Actions Permissions

Ensure GitHub Actions has the necessary permissions:

1. Go to Settings → Actions → General
2. Under "Workflow permissions", select:
   - ✅ Read and write permissions
   - ✅ Allow GitHub Actions to create and approve pull requests
3. Click "Save"

### 3. Branch Protection (Optional but Recommended)

To enforce CI checks before merging:

1. Go to Settings → Branches
2. Click "Add rule" or edit the existing rule for `main`
3. Branch name pattern: `main`
4. Enable:
   - ✅ Require a pull request before merging
   - ✅ Require status checks to pass before merging
   - Select required checks:
     - `Tests` (all matrix combinations)
     - `Laravel Pint`
   - ✅ Require branches to be up to date before merging
5. Click "Create" or "Save changes"

### 4. Dependabot Configuration

Dependabot is already configured and will:

- Check for Composer dependency updates weekly (Mondays)
- Check for GitHub Actions updates weekly (Mondays)
- Create PRs automatically
- Request review from @vasiliishvakin
- Add appropriate labels

**Optional: Enable Dependabot Security Updates**

1. Go to Settings → Code security and analysis
2. Enable "Dependabot security updates"

## 📊 Badges

The README.md already includes badges for:

- ✅ Tests status
- ✅ Code style status
- ✅ Code coverage (Codecov)
- ✅ Latest stable version (Packagist)
- ✅ Total downloads (Packagist)
- ✅ License

Badges will start working once:
- The repository is pushed to GitHub
- First workflow run completes
- Codecov is configured
- Package is published to Packagist

## 🚀 Testing Locally

Before pushing, you can test the workflows locally:

### Run Tests
```bash
composer test
```

### Run Tests with Coverage
```bash
composer test:coverage
```

### Check Code Style
```bash
composer lint:test
```

### Fix Code Style
```bash
composer lint
```

## 🔍 What Happens on Push

When you push code or create a PR:

1. **Tests Workflow** runs:
   - Tests on PHP 8.3 and 8.4
   - Tests with Laravel 12.x
   - Tests with prefer-lowest and prefer-stable
   - Generates code coverage report
   - Uploads coverage to Codecov

2. **Code Style Workflow** runs:
   - Checks code style with Laravel Pint
   - Fails if code doesn't meet style guidelines

3. **Codecov** analyzes:
   - Posts coverage report as PR comment
   - Shows coverage diff
   - Fails if coverage drops below threshold (80%)

4. **Dependabot** (weekly):
   - Checks for outdated dependencies
   - Creates PRs for updates
   - Groups related updates when possible

## 📝 Next Steps

1. Push the repository to GitHub
2. Configure Codecov token in GitHub Secrets
3. Wait for first workflow run to complete
4. Check that all workflows pass
5. Verify badges are working in README
6. (Optional) Configure branch protection rules
7. (Optional) Publish to Packagist

## 🆘 Troubleshooting

### Codecov Upload Fails

- Ensure `CODECOV_TOKEN` is set in GitHub Secrets
- Check that the token is valid and not expired
- Verify the repository is added to your Codecov account

### Tests Fail

- Check the workflow logs in GitHub Actions
- Reproduce locally with `composer test`
- Ensure all dependencies are up to date

### Code Style Fails

- Run `composer lint` to fix issues
- Run `composer lint:test` to check without fixing
- Review `pint.json` for style rules

### Dependabot PRs Not Appearing

- Check Settings → Code security and analysis
- Ensure Dependabot is enabled
- Wait until Monday for the first check
- Check Dependabot logs in Insights → Dependency graph → Dependabot
