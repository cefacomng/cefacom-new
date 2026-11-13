#!/bin/bash
# Sandbox-specific: paths assume /home/claude/cefacom and /home/claude/tools/composer.phar. Rebuilds the ZIP from tracked files + .git, unpacks it in an empty dir, then builds, tests and (with the argument "browser") browser-checks it. Set CEFACOM_DB_PASSWORD to the password of the MySQL user in .env (never commit it).
export COMPOSER_ALLOW_SUPERUSER=1 PLAYWRIGHT_BROWSERS_PATH=/opt/pw-browsers
SRC=/home/claude/cefacom; Z=/tmp/cefacom-pre.zip; rm -f $Z
mkdir -p /var/run/mysqld && chown mysql:mysql /var/run/mysqld
pgrep -x mysqld >/dev/null || (setsid mysqld --user=mysql >/tmp/mysqld.log 2>&1 &)
redis-cli ping >/dev/null 2>&1 || (setsid redis-server --daemonize yes >/dev/null 2>&1)
for i in $(seq 1 30); do mysqladmin -uroot ping >/dev/null 2>&1 && break; sleep 1; done
(cd $SRC && echo "uncommitted changes in source: $(git status --short | wc -l)" && git ls-files -z | xargs -0 zip -q $Z && zip -qr $Z .git)
rm -rf /tmp/clean && mkdir /tmp/clean && cd /tmp/clean && unzip -q $Z
echo "unzipped: $(git log --oneline | wc -l) commits, git status changes: $(git status --short | wc -l)"
mysql -uroot -e "DROP DATABASE IF EXISTS cefacom_clean; CREATE DATABASE cefacom_clean CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php /home/claude/tools/composer.phar install --no-interaction --no-progress --no-ansi > /tmp/c-install.log 2>&1; echo "composer install exit: $?"
npm ci --no-audit --no-fund > /tmp/c-npm.log 2>&1; echo "npm ci exit: $?"
cp .env.example .env; sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${CEFACOM_DB_PASSWORD:?set CEFACOM_DB_PASSWORD}/; s/^DB_DATABASE=.*/DB_DATABASE=cefacom_clean/" .env
php artisan key:generate --no-ansi >/dev/null 2>&1
php artisan migrate --force --no-ansi > /tmp/c-mig.log 2>&1; echo "migrate exit: $? ($(grep -c DONE /tmp/c-mig.log) DONE lines)"
php artisan cefacom:sync-permissions --no-ansi >/dev/null 2>&1
php artisan cefacom:check --no-ansi > /tmp/c-check.log 2>&1; echo "cefacom:check exit: $?"
php artisan test --no-ansi > /tmp/c-test.log 2>&1; echo "php tests exit: $?"; grep -aE "Tests:" /tmp/c-test.log | sed 's/\x1b\[[0-9;]*m//g'
npm test --silent > /tmp/c-vitest.log 2>&1; echo "vitest exit: $?"; grep -aE "Tests  " /tmp/c-vitest.log | sed 's/\x1b\[[0-9;]*m//g'
npm run typecheck --silent > /tmp/c-tsc.log 2>&1; echo "typecheck exit: $?"
npm run build --silent > /tmp/c-build.log 2>&1; echo "build exit: $?"; ls public/build/manifest.json
if [ "$1" = "browser" ]; then
  pkill -x php
  (cd /tmp/clean && setsid php artisan serve --host=127.0.0.1 --port=8000 >/tmp/serve-clean.log 2>&1 &)
  for i in $(seq 1 20); do [ "$(curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/login)" = "200" ] && break; sleep 1; done
  echo "clean copy /login: $(curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/login)"
  (cd /tmp/clean && CEFACOM_BASE=http://localhost:8000 timeout 280 python3 docs/verification/auth_flow_check.py > /tmp/c-e2e.log 2>&1); echo "auth_flow_check exit: $?"
  grep -a "PROBLEMS" /tmp/c-e2e.log
  python3 - <<'PY'
import json
t=open('/tmp/c-e2e.log').read(); j=json.loads(t[t.index('{'):t.rindex('}')+1])
print("checks:", len(j), "| false:", [k for k,v in j.items() if v is False], "| axe not clean:", [k for k,v in j.items() if k.startswith('axe_') and v!='0 violations'], "| console:", j.get('console_errors'))
PY
  pkill -x php
fi
