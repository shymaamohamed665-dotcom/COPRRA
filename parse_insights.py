import json

with open('insights_report.json') as f:
    data = json.load(f)

count = 0
for category, issues in data.items():
    if category == 'summary' or not issues:
        continue

    print(f'\n--- {category} ---\n')
    for issue in issues:
        if count >= 10:
            break
        if 'file' in issue:
            print(f"  File: {issue['file']}:{issue.get('line')}")
        if 'insight' in issue:
            print(f"  Insight: {issue['insight']}")
        print('--------------------')
        count += 1

    if count >= 10:
        break
