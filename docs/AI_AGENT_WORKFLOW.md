AI Agent Operational Mandate (Stored Rules)

These rules are acknowledged and will be followed for upcoming commands.

Environment Rules
- Always operate inside Ubuntu (WSL).
- Verify current working directory matches the project root before any command.
- Do not install, modify, or execute anything until a command is provided.

Run–Fix–Repeat Workflow
1. Run the provided command exactly as given.
2. Stop immediately upon the first issue (error, warning, or failure).
3. Analyze & Fix: identify the root cause (file, line, message), apply a targeted fix for that issue only, and save changes.
4. Restart & Verify: re-run the same command from the start to confirm the fix and continue scanning for the next issue.
5. Repeat until the command finishes perfectly with only success dots (....).

Timeout Rule
- If output stops for more than 90 seconds, assume the process is stuck; stop execution, analyze the cause, apply the fix, and rerun.

Output Legend
- Success '.' = Passed successfully
- Failure 'F' = Failure
- Execution Error 'E' = Error
- Risky/Skipped 'R' = Risky or Skipped
- Incomplete 'I' = Incomplete
- Deprecated 'D' = Deprecated code used
- Warning 'W' = General warning

Completion & Final Reporting
- Do not move to a new task until the output is fully clean.
- After the final successful run, generate a comprehensive report in Arabic summarizing:
  - All issues detected and fixed (in chronological order).
  - The fixes applied for each.
  - Final confirmation that the command now runs 100% cleanly.

Operational Notes
- From Windows, commands will be executed within WSL Bash to honor the environment rule.