# Quick Start Guide - CI Quality Automation

## 🚀 Getting Started in 5 Minutes

### Step 1: Install Development Dependencies (1 min)

```bash
# Using Composer
composer require --dev \
    phpstan/phpstan \
    phpstan/phpstan-symfony \
    phpstan/phpstan-doctrine \
    friendsofphp/php-cs-fixer \
    vimeo/psalm \
    phploc/phploc

# Or using Make
make install-dev
```

### Step 2: Run Your First Quality Check (2 min)

```bash
# Option 1: Using Make (recommended)
make quality-check

# Option 2: Manual commands
composer validate --strict
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run
vendor/bin/phpunit
```

### Step 3: Fix Any Issues (2 min)

```bash
# Auto-fix code style
make cs-fix

# Or manually
vendor/bin/php-cs-fixer fix
```

## 🎯 Common Commands

### Daily Development

```bash
# Before committing
make quality-check       # Run all checks

# After changes
make test               # Run tests only
make cs-fix             # Fix code style
```

### Weekly Maintenance

```bash
# Check security
make security           # Run security audit

# Full analysis
make full-check         # Comprehensive analysis
```

### Pre-Commit

```bash
# Simulate CI locally
make ci-local           # Full CI pipeline simulation
```

## 📋 Make Commands Reference

| Command | Description | Time |
|---------|-------------|------|
| `make help` | Show all available commands | instant |
| `make install` | Install dependencies | 30s |
| `make install-dev` | Install dev dependencies | 1min |
| `make test` | Run PHPUnit tests | 10s-1min |
| `make coverage` | Generate coverage report | 30s-2min |
| `make phpstan` | Run static analysis | 15s-1min |
| `make cs-check` | Check code style | 5s-30s |
| `make cs-fix` | Fix code style | 5s-30s |
| `make security` | Security checks | 10s-30s |
| `make quality-check` | All quality checks | 1-3min |
| `make ci-local` | Simulate CI pipeline | 2-5min |

## 🎨 IDE Integration

### PhpStorm

1. **PHPStan:**
   - Settings → PHP → Quality Tools → PHPStan
   - Configuration file: `phpstan.neon`

2. **PHP-CS-Fixer:**
   - Settings → PHP → Quality Tools → PHP CS Fixer
   - Configuration: `.php-cs-fixer.php`
   - Enable "On save"

3. **PHPUnit:**
   - Settings → PHP → Test Frameworks
   - Use Composer autoloader
   - Default config: `app/phpunit.xml`

### VS Code

Install extensions:
- `bmewburn.vscode-intelephense-client` (PHP IntelliSense)
- `junstyle.php-cs-fixer` (PHP CS Fixer)
- `SanderRonde.phpstan-vscode` (PHPStan)

Add to `.vscode/settings.json`:
```json
{
    "php-cs-fixer.executablePath": "vendor/bin/php-cs-fixer",
    "php-cs-fixer.onsave": true,
    "phpstan.enabled": true,
    "phpstan.configFile": "./phpstan.neon"
}
```

## 🔧 Git Hooks Setup (Optional)

### Pre-Commit Hook

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash

echo "Running pre-commit checks..."

# Run quality checks
make quality-check

if [ $? -ne 0 ]; then
    echo "❌ Quality checks failed. Commit aborted."
    echo "Fix issues or use 'git commit --no-verify' to skip"
    exit 1
fi

echo "✅ Pre-commit checks passed!"
exit 0
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

### Pre-Push Hook

Create `.git/hooks/pre-push`:

```bash
#!/bin/bash

echo "Running pre-push checks..."

# Simulate CI
make ci-local

if [ $? -ne 0 ]; then
    echo "❌ CI simulation failed. Push aborted."
    exit 1
fi

echo "✅ Pre-push checks passed!"
exit 0
```

Make it executable:
```bash
chmod +x .git/hooks/pre-push
```

## 📊 Understanding Results

### PHPStan Levels

| Level | Strictness | Recommendation |
|-------|-----------|----------------|
| 0-2 | Basic | Starting point |
| 3-5 | **Recommended** | Good balance |
| 6-7 | Strict | Advanced teams |
| 8-9 | Very strict | Greenfield projects |

### Code Coverage Targets

| Coverage | Status | Action |
|----------|--------|--------|
| < 50% | 🔴 Low | Write more tests |
| 50-70% | 🟡 Fair | Improve coverage |
| 70-85% | 🟢 Good | Maintain level |
| > 85% | ✅ Excellent | Keep it up! |

### Code Style Violations

- **PSR-2 violations:** Auto-fixable with `make cs-fix`
- **Symfony violations:** Auto-fixable with `make cs-fix`
- **Custom rules:** Review `.php-cs-fixer.php`

## 🐛 Troubleshooting

### "PHPStan not found"

```bash
# Reinstall dev dependencies
make install-dev
```

### "Out of memory"

```bash
# Increase PHP memory limit
php -d memory_limit=2G vendor/bin/phpstan analyse
```

### "Tests failing"

```bash
# Clear cache
rm -rf app/cache/*

# Regenerate autoload
composer dump-autoload
```

### "Code style conflicts"

```bash
# Reset cache
rm .php-cs-fixer.cache

# Rerun
make cs-fix
```

## 📚 Next Steps

1. **Read Full Documentation:** [`CI_SETUP.md`](./CI_SETUP.md)
2. **Configure GitHub Actions:** Copy workflows to `.github/workflows/`
3. **Set Up Pre-Commit Hooks:** See Git Hooks section above
4. **Integrate with IDE:** See IDE Integration section
5. **Run First CI Simulation:** `make ci-local`

## 🎓 Learning Resources

- **PHPStan:** https://phpstan.org/user-guide/getting-started
- **PHP-CS-Fixer:** https://cs.symfony.com/
- **PHPUnit:** https://phpunit.de/getting-started.html
- **Make Tutorial:** https://makefiletutorial.com/

## 💡 Pro Tips

1. **Run `make cs-fix` before every commit** - Saves time in CI
2. **Use `make watch-tests`** - Continuous testing during development
3. **Check `make help`** - Discover all available commands
4. **Set up IDE integration** - Instant feedback while coding
5. **Run `make ci-local` before pushing** - Catch issues early

---

**Need Help?** Check [`CI_SETUP.md`](./CI_SETUP.md) or open an issue.

**Last Updated:** 2025-11-11
