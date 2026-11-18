#!/usr/bin/env python3
"""
Jira Helper Client for SI4805 KELH
Reads credentials from .env and interacts with Jira Cloud REST API.
"""
import os
import sys
import json
import base64
import urllib.request
import urllib.error
from pathlib import Path

# Load .env file manually without external dependencies
env_path = Path(__file__).resolve().parent.parent / '.env'
if env_path.exists():
    with open(env_path, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                key, val = line.split('=', 1)
                os.environ.setdefault(key.strip(), val.strip().strip('"').strip("'"))

JIRA_URL = os.environ.get('JIRA_INSTANCE_URL', 'https://andi-muh-arif-darma-saputra-m.atlassian.net').rstrip('/')
JIRA_EMAIL = os.environ.get('JIRA_USER_EMAIL', 'andimuharifdarma@gmail.com')
JIRA_TOKEN = os.environ.get('JIRA_API_TOKEN', '')
JIRA_PROJECT = os.environ.get('JIRA_PROJECT_KEY', 'PN9KEIT')

def get_auth_header():
    raw = f"{JIRA_EMAIL}:{JIRA_TOKEN}"
    encoded = base64.b64encode(raw.encode('utf-8')).decode('utf-8')
    return {
        'Authorization': f'Basic {encoded}',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }

def request(endpoint, method='GET', payload=None):
    url = f"{JIRA_URL}{endpoint}"
    data = json.dumps(payload).encode('utf-8') if payload else None
    req = urllib.request.Request(url, data=data, method=method, headers=get_auth_header())
    try:
        with urllib.request.urlopen(req) as resp:
            content = resp.read().decode('utf-8')
            return json.loads(content) if content else {}
    except urllib.error.HTTPError as e:
        err_msg = e.read().decode('utf-8')
        print(f"Error {e.code}: {err_msg}", file=sys.stderr)
        return None
    except Exception as e:
        print(f"Request failed: {e}", file=sys.stderr)
        return None

def list_issues(jql=None, max_results=50):
    if not jql:
        jql = f"project = {JIRA_PROJECT} ORDER BY created DESC"
    payload = {
        'jql': jql,
        'maxResults': max_results,
        'fields': ['summary', 'status', 'issuetype', 'priority', 'assignee', 'created', 'updated']
    }
    res = request('/rest/api/3/search/jql', method='POST', payload=payload)
    if not res:
        return []
    return res.get('issues', [])

def get_issue(issue_key):
    return request(f'/rest/api/3/issue/{issue_key}')

def get_transitions(issue_key):
    return request(f'/rest/api/3/issue/{issue_key}/transitions')

def transition_issue(issue_key, transition_id):
    payload = {'transition': {'id': transition_id}}
    return request(f'/rest/api/3/issue/{issue_key}/transitions', method='POST', payload=payload)

def add_comment(issue_key, comment_text):
    # Atlassian Document Format (ADF)
    payload = {
        'body': {
            'type': 'doc',
            'version': 1,
            'content': [
                {
                    'type': 'paragraph',
                    'content': [{'type': 'text', 'text': comment_text}]
                }
            ]
        }
    }
    return request(f'/rest/api/3/issue/{issue_key}/comment', method='POST', payload=payload)

def set_status(issue_key, status_name):
    # Fetch available transitions dynamically from Jira
    res = get_transitions(issue_key)
    if not res or 'transitions' not in res:
        print(f"Gagal mengambil transitions untuk {issue_key}")
        return False
    
    normalized_target = status_name.lower().replace(' ', '').replace('_', '').replace('-', '')
    target_transition = None
    for t in res.get('transitions', []):
        t_name = t.get('name', '').lower().replace(' ', '').replace('_', '').replace('-', '')
        if t_name == normalized_target:
            target_transition = t
            break

    if not target_transition:
        available = [t.get('name') for t in res.get('transitions', [])]
        print(f"Status '{status_name}' tidak valid. Pilihan status yang tersedia: {available}")
        return False

    trans_id = target_transition['id']
    trans_res = transition_issue(issue_key, trans_id)
    return trans_res is not None

if __name__ == '__main__':
    args = sys.argv[1:]
    if not args or args[0] == 'list':
        issues = list_issues()
        print(f"\n--- Jira Issues for Project [{JIRA_PROJECT}] ({len(issues)} found) ---")
        for iss in issues:
            k = iss['key']
            f = iss['fields']
            summary = f.get('summary', '')
            status = f.get('status', {}).get('name', 'Unknown')
            itype = f.get('issuetype', {}).get('name', 'Task')
            assignee = f.get('assignee', {})
            assignee_name = assignee.get('displayName', 'Unassigned') if assignee else 'Unassigned'
            print(f"[{k}] ({status}) [{itype}] {summary} | Assigned to: {assignee_name}")
    elif args[0] == 'view' and len(args) > 1:
        issue = get_issue(args[1])
        if issue:
            f = issue.get('fields', {})
            print(f"Key: {issue.get('key')}")
            print(f"Summary: {f.get('summary')}")
            print(f"Status: {f.get('status', {}).get('name')}")
            print(f"Assignee: {f.get('assignee', {}).get('displayName', 'Unassigned') if f.get('assignee') else 'Unassigned'}")
    elif args[0] == 'my':
        issues = list_issues(jql=f"project = {JIRA_PROJECT} AND assignee = currentUser()")
        print(f"\n--- My Jira Issues ({len(issues)} found) ---")
        for iss in issues:
            k = iss['key']
            f = iss['fields']
            print(f"[{k}] ({f.get('status', {}).get('name')}) {f.get('summary')}")
    elif args[0] == 'status' and len(args) > 2:
        key = args[1]
        st = args[2]
        if set_status(key, st):
            print(f"Berhasil mengubah status {key} menjadi {st}")
        else:
            print(f"Gagal mengubah status {key}")
    else:
        print("Usage:")
        print("  python3 scripts/jira_client.py list")
        print("  python3 scripts/jira_client.py my")
        print("  python3 scripts/jira_client.py view <KEY>")
        print("  python3 scripts/jira_client.py status <KEY> <todo|in_progress|review|done>")
