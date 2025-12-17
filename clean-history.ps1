# Script to remove confidential info from git history
$env:FILTER_BRANCH_SQUELCH_WARNING = "1"

# Create a backup branch first
git branch backup-before-clean

# Use git filter-branch to replace sensitive strings in all commits
git filter-branch --force --tree-filter '
if [ -f api/send-password-reset.php ]; then
    sed -i "s/stdhubsup@gmail.com/YOUR_EMAIL@gmail.com/g" api/send-password-reset.php
    sed -i "s/jhpt zsfv abkl dwan/YOUR_APP_PASSWORD/g" api/send-password-reset.php
fi
if [ -f api/send-verification-email.php ]; then
    sed -i "s/stdhubsup@gmail.com/YOUR_EMAIL@gmail.com/g" api/send-verification-email.php
    sed -i "s/jhpt zsfv abkl dwan/YOUR_APP_PASSWORD/g" api/send-verification-email.php
fi
if [ -f forgot-password.php ]; then
    sed -i "s/stdhubsup@gmail.com/YOUR_EMAIL@gmail.com/g" forgot-password.php
    sed -i "s/jhpt zsfv abkl dwan/YOUR_APP_PASSWORD/g" forgot-password.php
fi
' --prune-empty --tag-name-filter cat -- --all

