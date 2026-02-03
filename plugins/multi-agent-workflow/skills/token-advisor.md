---
name: token-advisor
description: "Analyzes current session and suggests token optimization strategies. Use when session feels slow, before long tasks, or to proactively manage context. <example>Context: Mid-session, working on complex feature.\\nuser: \"Session feels slow\"\\nassistant: \"Let me analyze with token-advisor\"</example>"
model: inherit
---

# Token Advisor Skill

Analyzes session patterns and provides actionable recommendations to optimize token usage.

## When to Use

- Session responses feel slower than usual
- Working on a complex, multi-file task
- Planning to continue for another hour+
- Before starting a new major task
- Proactively every 30-45 minutes on long sessions

## Invocation

```bash
# Basic analysis
/skill:token-advisor

# With auto-suggestions
/skill:token-advisor --auto-suggest

# Quick check only
/skill:token-advisor --quick
```

## Analysis Process

### Step 1: Session Health Check

Evaluate current session state:

```markdown
## Session Health Analysis

### Context Indicators
| Metric | Value | Status |
|--------|-------|--------|
| Estimated capacity used | ~X% | 🟢/🟡/🔴 |
| Files read this session | N | 🟢/🟡/🔴 |
| Session duration | Xh Xm | 🟢/🟡/🔴 |
| Task switches | N | 🟢/🟡/🔴 |

### Status Legend
- 🟢 Healthy (< 50% / < 10 files / < 1h / < 2 switches)
- 🟡 Monitor (50-70% / 10-15 files / 1-2h / 2-3 switches)
- 🔴 Action needed (> 70% / > 15 files / > 2h / > 3 switches)
```

### Step 2: Pattern Detection

Identify inefficient patterns in the session:

```markdown
## Detected Patterns

### Potential Inefficiencies
| Pattern | Occurrences | Impact | Suggestion |
|---------|-------------|--------|------------|
| Full file reads | X | High | Use grep first, then targeted reads |
| Large command outputs | X | Medium | Add filters (--oneline, head, etc.) |
| Repeated file reads | X | Medium | Reference previous content |
| Context switches | X | Low | Consider separate sessions |
```

### Step 3: MCP Analysis

Check MCP server overhead:

```markdown
## MCP Server Status

| Server | Status | Used This Session | Recommendation |
|--------|--------|-------------------|----------------|
| postgres | Active | Yes | Keep |
| github | Active | No | Consider disabling |
| slack | Active | No | Consider disabling |
| puppeteer | Active | No | Consider disabling |

**Potential savings**: Disabling unused servers could reduce context overhead.
```

### Step 4: Recommendations

Provide prioritized action items:

```markdown
## Recommendations

### Immediate Actions (Do Now)
1. **[Action]** - [Reason] - [Command if applicable]

### Preventive Actions (Before Next Task)
1. **[Action]** - [Reason]

### Session Strategy
- [ ] Continue current session
- [ ] Create snapshot and continue
- [ ] Compact and continue
- [ ] Start fresh session (recommended if 🔴)
```

## Output Format

### Quick Mode (--quick)

```markdown
## Token Advisor Quick Check

**Status**: 🟢 Healthy / 🟡 Monitor / 🔴 Action Needed

**Key Metric**: ~X% context used

**Top Recommendation**: [Single most impactful action]
```

### Full Mode (default)

```markdown
## Token Advisor Report

### Session Health
[Health check table]

### Detected Issues
[Pattern detection results]

### MCP Status
[MCP analysis]

### Action Plan
[Prioritized recommendations]

### Commands to Run
\`\`\`bash
[Specific commands based on analysis]
\`\`\`
```

### Auto-Suggest Mode (--auto-suggest)

Provides recommendations without detailed analysis:

```markdown
## Token Optimization Suggestions

Based on session patterns, consider:

1. ✅ [Already optimized aspect]
2. ⚠️ [Suggested improvement] → `[command]`
3. ⚠️ [Suggested improvement] → `[command]`
```

## Common Recommendations

### When Context is High (>70%)

```bash
# Option 1: Compact current context
/compact

# Option 2: Snapshot and start fresh
/workflows:snapshot --name="checkpoint-$(date +%H%M)"
# Then start new session with /workflows:restore
```

### When Many Files Read (>15)

```markdown
Consider:
1. Are all read files still relevant?
2. Can you work from summaries instead?
3. Would a fresh session with targeted reads be faster?
```

### When Session is Long (>2h)

```markdown
Recommended flow:
1. Create snapshot: /workflows:snapshot --name="2h-checkpoint"
2. Note current task and next steps
3. Start fresh session
4. Restore: /workflows:restore --name="2h-checkpoint"
5. Continue with clean context
```

### When MCP Overhead Detected

```markdown
Unused MCP servers consume context. To optimize:
1. Check which servers are actually needed
2. Disable unused servers in .ai/extensions/mcp/servers.yaml
3. Re-enable when needed for specific tasks
```

## Integration with Workflow

### Suggested Trigger Points

| Workflow Stage | Token Advisor Action |
|----------------|---------------------|
| After `/workflows:plan` | Quick check before implementation |
| Mid-implementation | Full analysis if slowing down |
| Before `/workflows:review` | Ensure context for thorough review |
| Before complex debugging | Check capacity for investigation |

### Automatic Reminders

The workflow may suggest running token-advisor when:
- Session exceeds 1 hour
- More than 10 files have been read
- User reports slow responses
- Before starting a new major task

## Best Practices

1. **Proactive > Reactive**: Check early, don't wait for slowdown
2. **Snapshot Before Compact**: Preserve context in case needed later
3. **One Task Focus**: Mixing tasks fragments context
4. **Grep Before Read**: Always search before full file reads
5. **Filter Outputs**: Use `--oneline`, `--stat`, `head`, etc.

## Related

- `/context` - Check current usage
- `/compact` - Summarize context
- `/clear` - Start fresh
- `/workflows:snapshot` - Preserve session state
- `SESSION_CONTINUITY.md` - Detailed context management guide
