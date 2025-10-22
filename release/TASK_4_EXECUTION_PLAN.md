TASK 4 — Execution Plan (Batches of 4, isolated runs)

Goal: Run every runnable item (tests/tools) individually in isolated terminals in groups of 4. Capture full stdout/stderr/logs. Do not repair anything — record only.

Directory for outputs: /var/www/html/reports/task4/
Structure per item:

- reports/task4/<seq>-<safe-name>/stdout.log
- reports/task4/<seq>-<safe-name>/stderr.log
- reports/task4/<seq>-<safe-name>/meta.json (contains item name, path, command, env, start/end timestamps, exit code)

Execution rules:

- Each terminal runs exactly one command (one test/tool) and exits.
- Run 4 terminals in parallel, wait for all to finish, then collect outputs and compress them if desired.
- Do not run combined scripts that execute multiple tests in one process (e.g., `run_all_450_tests.sh`) unless that script is explicitly an item — preference is to run each test command directly as listed in the inventory.

Example run command (isolated):

- cd /var/www/html
- export APP_ENV=testing
- time php -d memory_limit=1G ./vendor/bin/phpunit --filter ProductTest --log-junit=reports/task4/001-ProductTest/junit.xml

Batch orchestration suggestion (automation helper):

- Use `execute_task4_batch_runner.sh` which exists in the repository. Per Task 0, we will only run the script to capture outputs; we must ensure it respects single-test-per-process semantics. If it runs multiple tests internally, prefer running the individual phpunit commands produced from inventory.

Next steps:

- I'll create the directory structure for reports and a small runner script `reports/task4/run_batch.sh` that takes a list of commands and executes them in parallel (4 at a time), capturing outputs. This runner will be read-only in terms of code changes and will only write logs into the reports folder.

Note: Before executing any test/tool, I'll present the first batch of 4 commands for confirmation and then proceed to run them and collect outputs (strictly per Task 0 rules).
