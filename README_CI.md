# 🚀 CI Quality Automation for ResyMF CMS

Complete continuous integration and quality automation setup for the ResyMF CMS Symfony project.

## 📦 What's Included

### GitHub Actions Workflows

1. **`ci-quality.yml`** - Main quality pipeline
   - ✅ Multi-PHP version testing (5.6 - 7.4)
   - ✅ PHPStan static analysis (Level 5)
   - ✅ PHP-CS-Fixer code style checking
   - ✅ PHPUnit tests with coverage
   - ✅ Doctrine schema validation
   - ✅ Code metrics (PHPLOC)
   - ✅ PHP syntax linting

2. **`security-scan.yml`** - Security scanning pipeline
   - 🛡️ Composer security audit
   - 🛡️ Dependency vulnerability checking
   - 🛡️ OWASP dependency check
   - 🛡️ PHP security checker
   - 🛡️ Psalm taint analysis

### Configuration Files

| File | Purpose |
|------|---------|
| `phpstan.neon` | PHPStan static analysis configuration |
| `.php-cs-fixer.php` | PHP-CS-Fixer code style rules |
| `phpunit.xml.dist` | PHPUnit test configuration |
| `Makefile` | Development automation commands |
| `.githooks/pre-commit` | Pre-commit quality checks |
| `.githooks/pre-push` | Pre-push CI simulation |

### Documentation

| File | Description |
|------|-------------|
| `CI_SETUP.md` | Complete CI setup documentation |
| `QUICKSTART.md` | 5-minute quick start guide |
| `README_CI.md` | This file |
| `composer-dev-requirements.json` | Dev dependencies reference |

## 🎯 Quick Start

### 1. Install (30 seconds)

```bash
# Clone the repository
git clone <your-repo-url>
cd resymf-cms

# Install dependencies
make install-dev

# Or manually
composer require --dev phpstan/phpstan phpstan/phpstan-symfony \
    friendsofphp/php-cs-fixer vimeo/psalm phploc/phploc
```

### 2. Run Quality Checks (1 minute)

```bash
# Run all quality checks
make quality-check

# Or individual checks
make phpstan      # Static analysis
make cs-check     # Code style
make test         # Unit tests
make security     # Security audit
```

### 3. Fix Issues (automatic)

```bash
# Auto-fix code style
make cs-fix

# Run full check again
make quality-check
```

## 📊 CI Pipeline Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Push to Repository                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
        ┌───────────────────┴───────────────────┐
        ↓                                       ↓
┌───────────────────┐                  ┌────────────────────┐
│  CI Quality       │                  │  Security Scan     │
│  (Every Push)     │                  │  (Weekly/Push)     │
└───────────────────┘                  └────────────────────┘
        ↓                                       ↓
┌───────────────────┐                  ┌────────────────────┐
│ • Validate        │                  │ • Composer Audit   │
│ • PHPStan L5      │                  │ • Dependency Check │
│ • Code Style      │                  │ • OWASP Scan       │
│ • PHPUnit Tests   │                  │ • Security Checker │
│ • Coverage        │                  │ • Psalm Taint      │
│ • Doctrine        │                  └────────────────────┘
│ • Metrics         │
│ • Lint            │
└───────────────────┘
        ↓
┌───────────────────┐
│   All Passed ✓    │
│   Ready to Merge  │
└───────────────────┘
```

## 🎨 Development Workflow

### Daily Development

```bash
# 1. Start working on a feature
git checkout -b feature/new-feature

# 2. Write code + tests

# 3. Before committing
make quality-check    # Ensure quality
make cs-fix          # Auto-fix style

# 4. Commit
git add .
git commit -m "Add new feature"
```

### With Git Hooks (Recommended)

```bash
# Setup once
cp .githooks/pre-commit .git/hooks/pre-commit
cp .githooks/pre-push .git/hooks/pre-push
chmod +x .git/hooks/pre-commit .git/hooks/pre-push

# Now hooks run automatically
git commit -m "Your message"  # → Runs quality checks
git push                      # → Runs CI simulation
```

### Before Pull Request

```bash
# Simulate full CI pipeline
make ci-local

# Or run comprehensive analysis
make full-check
```

## 📈 Quality Standards

### Code Coverage

| Coverage | Status | Required Action |
|----------|--------|-----------------|
| < 50% | 🔴 Critical | Write more tests immediately |
| 50-70% | 🟡 Warning | Improve coverage gradually |
| 70-85% | 🟢 Good | Maintain or improve |
| > 85% | ✅ Excellent | Keep up the good work! |

**Target:** 70% minimum for merge approval

### PHPStan Analysis

| Level | Description | Project Standard |
|-------|-------------|------------------|
| 0-2 | Basic checks | Starting point |
| 3-4 | Intermediate | Transition level |
| **5** | **Recommended** | **Current standard** ✓ |
| 6-7 | Strict | Future goal |
| 8-9 | Maximum | Greenfield only |

**Current Level:** 5 (configurable in `phpstan.neon`)

### Code Style

- **Standard:** PSR-2 + Symfony conventions
- **Auto-fix:** `make cs-fix`
- **Enforcement:** Pre-commit hook + CI

## 🛠️ Make Commands

### Essential Commands

```bash
make help           # Show all commands
make install        # Install dependencies
make install-dev    # Install dev dependencies
make test           # Run tests
make coverage       # Generate coverage report
make quality-check  # Run all quality checks
make cs-fix         # Auto-fix code style
```

### Advanced Commands

```bash
make phpstan        # Static analysis
make psalm          # Psalm analysis
make psalm-security # Security taint analysis
make metrics        # Code metrics
make security       # Security checks
make full-check     # Comprehensive analysis
make ci-local       # Simulate CI
```

### Maintenance Commands

```bash
make validate       # Validate composer.json
make clean          # Clean build artifacts
make clean-all      # Clean everything
```

## 🔧 Configuration

### Adjusting PHPStan Level

Edit `phpstan.neon`:

```yaml
parameters:
    level: 6  # Increase from 5 to 6
```

### Customizing Code Style

Edit `.php-cs-fixer.php`:

```php
->setRules([
    '@PSR2' => true,
    '@Symfony' => true,
    'array_syntax' => ['syntax' => 'long'], // Change to long array syntax
    // Add more rules...
])
```

### Excluding Paths

**PHPStan:**
```yaml
# phpstan.neon
parameters:
    excludePaths:
        - src/Legacy/*
```

**PHP-CS-Fixer:**
```php
// .php-cs-fixer.php
$finder = PhpCsFixer\Finder::create()
    ->exclude('Legacy')
    ->exclude('Generated');
```

## 🎯 CI Status Badges

Add to your main `README.md`:

```markdown
![CI Quality](https://github.com/YOUR_USERNAME/resymf-cms/workflows/CI%20Quality%20Checks/badge.svg)
![Security](https://github.com/YOUR_USERNAME/resymf-cms/workflows/Security%20Scanning/badge.svg)
[![codecov](https://codecov.io/gh/YOUR_USERNAME/resymf-cms/branch/master/graph/badge.svg)](https://codecov.io/gh/YOUR_USERNAME/resymf-cms)
```

## 📚 File Structure

```
.
├── .github/
│   └── workflows/
│       ├── ci-quality.yml        # Main CI pipeline
│       └── security-scan.yml     # Security scanning
├── .githooks/
│   ├── pre-commit               # Pre-commit hook template
│   └── pre-push                 # Pre-push hook template
├── phpstan.neon                 # PHPStan configuration
├── .php-cs-fixer.php            # Code style configuration
├── phpunit.xml.dist             # PHPUnit configuration
├── Makefile                     # Development automation
├── CI_SETUP.md                  # Complete documentation
├── QUICKSTART.md                # Quick start guide
├── README_CI.md                 # This file
└── composer-dev-requirements.json  # Dev dependencies reference
```

## 🚀 Deployment to Production

### GitHub Actions Setup

1. **Copy workflows to main repository:**
   ```bash
   mkdir -p /path/to/main/repo/.github/workflows
   cp .github/workflows/* /path/to/main/repo/.github/workflows/
   ```

2. **Copy configuration files:**
   ```bash
   cp phpstan.neon /path/to/main/repo/
   cp .php-cs-fixer.php /path/to/main/repo/
   cp phpunit.xml.dist /path/to/main/repo/
   cp Makefile /path/to/main/repo/
   ```

3. **Add dev dependencies to composer.json:**
   ```bash
   # Add require-dev section from composer-dev-requirements.json
   # to your main composer.json
   ```

4. **Setup Codecov (optional):**
   - Visit https://codecov.io/
   - Connect your GitHub repository
   - Add `CODECOV_TOKEN` to GitHub Secrets

5. **Push and verify:**
   ```bash
   git add .
   git commit -m "Add CI quality automation"
   git push origin master
   ```

## 🐛 Troubleshooting

### Common Issues

**Issue:** PHPStan runs out of memory
```bash
# Solution: Increase memory limit
vendor/bin/phpstan analyse --memory-limit=2G
```

**Issue:** Tests failing unexpectedly
```bash
# Solution: Clear cache and regenerate autoload
rm -rf app/cache/*
composer dump-autoload
make test
```

**Issue:** Code style conflicts
```bash
# Solution: Reset cache and rerun
rm .php-cs-fixer.cache
make cs-fix
```

**Issue:** Hooks not executing
```bash
# Solution: Ensure hooks are executable
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/pre-push
```

## 📖 Additional Resources

- [Full CI Documentation](CI_SETUP.md)
- [Quick Start Guide](QUICKSTART.md)
- [PHPStan Documentation](https://phpstan.org/)
- [PHP-CS-Fixer Documentation](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
- [PHPUnit Documentation](https://phpunit.de/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)

## 🤝 Contributing

### Before Submitting PR

1. ✅ Run `make quality-check` - All checks pass
2. ✅ Run `make cs-fix` - Code style fixed
3. ✅ Run `make coverage` - Coverage ≥ 70%
4. ✅ Run `make security` - No critical vulnerabilities
5. ✅ Update tests - New features tested
6. ✅ Update docs - Documentation current

### PR Checklist

- [ ] All CI checks passing
- [ ] Code coverage maintained/improved
- [ ] No PHPStan errors introduced
- [ ] Code style compliant
- [ ] Tests added for new features
- [ ] Documentation updated
- [ ] Security audit clean

## 📊 Metrics & Reports

### Available Reports

| Report | Location | Description |
|--------|----------|-------------|
| Coverage HTML | `build/coverage/index.html` | Interactive coverage report |
| Coverage XML | `build/logs/clover.xml` | Machine-readable coverage |
| PHPLOC Metrics | `build/phploc.json` | Code metrics and statistics |
| PHPUnit JUnit | `build/logs/junit.xml` | Test results (JUnit format) |

### Viewing Reports

```bash
# Generate coverage report
make coverage

# Open in browser
open build/coverage/index.html

# View metrics
cat build/phploc.json | jq .
```

## 🎓 Best Practices

1. **Commit Often** - Small, focused commits
2. **Test First** - Write tests before code (TDD)
3. **Fix Style Early** - Run `make cs-fix` before committing
4. **Check Quality** - Run `make quality-check` before pushing
5. **Review Coverage** - Maintain ≥70% test coverage
6. **Update Dependencies** - Keep packages current
7. **Security First** - Address vulnerabilities immediately
8. **Document Changes** - Update docs with code

## 💡 Pro Tips

1. **Alias for speed:**
   ```bash
   # Add to ~/.bashrc or ~/.zshrc
   alias mq='make quality-check'
   alias mf='make cs-fix'
   alias mt='make test'
   ```

2. **IDE Integration:**
   - Enable PHPStan in PhpStorm/VS Code
   - Enable PHP-CS-Fixer "on save"
   - Configure PHPUnit test runner

3. **Watch mode for tests:**
   ```bash
   # Requires 'entr'
   make watch-tests
   ```

4. **Pre-commit hook:**
   - Catches issues before CI
   - Auto-fixes code style
   - Saves CI minutes

## 🆘 Getting Help

- **Documentation:** Read [CI_SETUP.md](CI_SETUP.md)
- **Quick Start:** Check [QUICKSTART.md](QUICKSTART.md)
- **Make Commands:** Run `make help`
- **Issues:** Check troubleshooting section above

## 📄 License

This CI configuration is part of the resymf-cms project and follows the same MIT license.

---

**Version:** 1.0.0
**Last Updated:** 2025-11-11
**Maintained By:** Development Team

**Status:** ✅ Production Ready
