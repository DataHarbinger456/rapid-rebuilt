# Rapid Rebuilt — Internal Pricing Strategy

**Not for the client.** This is your negotiation map: what to ask, what to concede,
where the floor is, and what to never give away.

---

## 1. The number you ask for

**$23,500 build, staged across five stages. $950/mo ongoing after launch.**

Do not present this as one number, and do not put $23,500 in front of him in one
sitting. Present it as small sequential decisions, because he is cash-sensitive
(his words: "practically bankrupt… $120k+ in" on a previous attempt). A $2k–$9k
decision is psychologically survivable. A $23,500 decision is not.

**Right now the only live ask is S2 at $9,500** ($4,750 to start). S2.5 gets quoted
after he has watched a state form fill itself. See §8.

| Stage | Price | Deposit to start | Balance on demo |
|---|---|---|---|
| S1 — Portal live | $3,500 | $1,750 | $1,750 |
| S2 — AI + official state forms | $9,500 | $4,750 | $4,750 |
| S2.5 — Inspection log + DMV access | $4,500 | $2,250 | $2,250 |
| M1 — Marketing foundation | $3,500 | $1,750 | $1,750 |
| M2 — Outreach engine | $2,500 | $1,250 | $1,250 |
| **Total** | **$23,500** | | |
| Ongoing | $950/mo | starts after M2 | |
| Extra state packs | $2,000–$3,000 ea | quoted separately, never bundled | |

---

## 2. What this is actually worth (your leverage)

An outside shop quoting this scope — three-role portal, payments, AI document
extraction, state-form automation, marketing site — lands at **$55k–$80k** and
3–5 months. Even an offshore team is ~$20k–$35k with heavy management risk and
no accountability.

**You are pricing at well under half of market — roughly 55–70% below.** That discount
is your single biggest lever. Name it explicitly, once, then never discount again. A
discount that keeps moving isn't a discount — it's an admission the price was fake.

**Say this:** *"Market for this is $55k–$80k. You're at less than half that because of
who you are. That's the friend price, and it's the only one I've got."*

---

## 3. Your walk-away floors

| Line | Floor | Reasoning |
|---|---|---|
| S1 | $3,500 | Already delivered. Non-negotiable — the work exists. |
| S2 | $8,000 | Hard floor. Below this the AI + form automation isn't worth building. |
| S2.5 | $3,500 | Schema exists, so the build is cheaper than it looks — but the DMV role and export are real work. |
| M1 | $3,000 | |
| M2 | $2,000 | Droppable entirely if he wants to self-serve outreach. |
| Monthly | $750 | Below $750 you're paying for his hosting and AI out of pocket. |
| **Build floor** | **$20,000** | Sum of every floor above. Walk below this. |

**Never concede:**
- **The 50% deposit.** Nothing starts on a handshake. This is the whole rule.
- **Ownership before final payment.** Code transfers when the stage is paid.
- **Free "just one more thing" scope.** Every add-on gets quoted separately.

---

## 4. Concession ladder (give in this order, only if pushed)

1. **Stretch the timeline**, not the price — "same total, spread over 6 months"
2. **Drop M2** ($2,500 off) — he does his own outreach; portal is unaffected
3. **Defer the monthly** to 60 days post-launch instead of immediately
4. **Split S2 into four payments** of $2,375 each instead of two of $4,750

Notice all four preserve the rate. You're selling time and scope, never price.

---

## 5. Objection handling

**"That's a lot of money right now."**
> "It's $4,750 to start, and that's the only decision on the table today. You see
> the forms filling themselves before anything else gets discussed."

**"I already spent $120k and got nothing."**
> "Exactly why we're doing it in stages. You've already seen S1 live — you're not
> paying on faith anymore, you're paying on something you clicked through yourself."

**"Can you do it cheaper?"**
> "The market number is $55k–$80k. I'm well under half that. I can reduce scope,
> not the rate — if M2 is out, that's $2,500 off and you handle your own dealer outreach."

**"Why is the inspection log extra? I thought that was included."**
> "It wasn't in what you approved — that covered the portal and the state forms.
> The log is a compliance system with its own access role for the state, its own
> export, and geotagged field photos. It's the thing that makes the Tallahassee
> conversation possible. Separate build, separate decision."

**"I need all the states, not just Florida."**
> "The reading engine works anywhere — that's the expensive part and it's in S2.
> Each state's forms are their own research and their own templates. Let's prove
> Florida, then add states in whatever order your volume justifies. Two to three
> thousand each." Never quote "all 50" as one number.

**"What's the $950/mo actually for?"**
> Point at the itemized table in the client doc. Hosting, AI usage, SMS, security
> patches, campaign ops, fixes with no hourly billing, priority support.
> It's a service contract, not a subscription to nothing.

**"Can I pay after it makes money?"**
> No. Politely, but no. Revenue-share means you carry his business risk for free.
> If he pushes hard, the answer is a longer schedule — never contingent payment.

**"Should I get my own Anthropic account / use my own AI key?"**
> "It's included. You'll never see a token bill." Then move on. Do not offer it,
> do not explain the cost, do not raise the topic yourself. See §5b.

---

## 5b. The AI key decision (settled 10 Aug 2026)

**You hold the Anthropic key. It is bundled into the $950/mo. Never itemised,
never passed through, never entered by the client.**

### The numbers that settle it

Measured, not estimated — 14 real salvage titles through the live API, and the
40 real cases in the database:

| | |
|---|---|
| Average case value | **$266.90** |
| AI cost per title read | **1.03¢** (Sonnet) |
| AI as share of one case | **0.004%** |
| At 100 titles/month | **~$1/month** |

**One case pays for roughly 26,000 AI reads.** Haiku would cut it to 0.36¢ but
reads VINs materially worse (4/14 valid check digits vs Sonnet's 8/14), so it is
not worth switching. Sonnet stays.

### Why not have him enter his own key

An earlier version of this plan had the client supply his key so Anthropic
billed him directly. That reasoning only holds when API cost is a real line
item. At a penny a title it is not — it was solving a problem that does not
exist.

What it would actually cost you:

- He must open an Anthropic developer account, attach a card, mint a key and
  paste it in. That is a sales objection and a support call, to save you about
  a dollar a month.
- It breaks when the key rotates or his card expires, and you get the call.
- **It reframes what you sell.** "Enter your API key" is software he installs.
  "$950/mo, everything included" is a service. The second is worth more, is
  stickier, and does not invite him to price your fee against a token bill.

### What the fee is actually covering

Hosting (~$20–50/mo), AI (~$1–5/mo), and **your time** — which is the whole
cost. Price off support and maintenance. The AI is rounding error: bundle it
silently and never bring up tokens.

### Do not build the in-app key screen yet

The key rotates from Laravel Cloud environment settings in under a minute, now
proven in production. A settings screen saves a redeploy and costs hours. Not
worth it at this scale.

What does already exist: every extraction row stores `prompt_tokens` and
`completion_tokens`, so true spend is one query away whenever you want it,
rather than an estimate.

### Revisit only if

- **A client goes high-volume** — thousands of titles a month, where usage
  becomes a real number worth attributing.
- **You sign multiple clients** and want per-tenant cost visibility.
- **Someone raises compliance.** With your key, documents flow through *your*
  Anthropic account. Fine at this scale, but it is the one honest argument for
  client-held keys and worth answering straight if asked.

---

## 6. What you've already delivered (proof, not promises)

S1 is **done and live**. This matters enormously in the conversation: you are not
selling a concept, you are invoicing for something he can click right now.

Delivered:
- Live production URL, SSL, automated nightly database backups, error monitoring
- Runtime modernized: PHP 8.1 → 8.3, Laravel 10 → 12 (both were end-of-life,
  meaning no security patches — a genuine liability he was carrying)
- Document upload fixed and moved to private cloud storage (it crashed before)
- Three working roles: customer, staff, inspector
- Magic-link login, public tracking link, full case timeline
- **17 real bugs found and fixed** during live testing, including several that made
  the previous build's core flow literally impossible to complete

**The strongest line available to you:** the previous build could not create a single
case. Not "was slow" or "looked dated" — the form wrote to database columns that
didn't exist, so it failed silently every time. That was $120k of unusable software.
Yours works, and he watched it work.

Use this factually, never gloatingly. The receipts speak louder than the adjectives.

---

## 7. Demo-day rules

1. **Reset the data first** (`php artisan demo:reset --force`) so he sees a clean start
2. **Run the whole flow yourself once** before he's watching — every single time
3. **Get ahead of the two known gaps** before he finds them: no email yet, payment
   is in test mode. Framing them as "next stage, already scoped" reads as planning.
   Letting him discover them reads as broken.
4. **Do not fix anything live in front of him.** Write it down, move on.
5. **End on the tracker link** — open it on your phone. Physical, tangible, memorable.

---

## 8. Post-demo read (3 Aug 2026)

He closed with *"I like where it's going"* and asked for zero refunds, zero pushback
on price — he pushed on **capability**, which is the good kind of pressure. Full
transcript in `docs/calls/2026-08-03-demo-call-transcript.txt`.

What he asked for that wasn't scoped:

| Ask | Where it landed | Notes |
|---|---|---|
| All states, not just Florida | Per-state packs, $2–3k each | Said it 90 seconds in, casually. He has not priced it in his head. |
| DMV-shareable inspection log | **S2.5**, $4,500 | His second-biggest ask. Quote it. |
| Inspection lookup by VIN | S2.5 | |
| Excel export of the log | S2.5 | |
| Inspector field photos | S2.5 | Schema already built, no UI — cheap for you, valuable to him. |
| Calendar on the tracker | Fold into S2 free | Throwaway remark, costs nothing, buys goodwill. |

Already covered, so **reassure rather than requote**: filled PDFs that print
(his loudest ask — S2), and rejection notifications (S1 email + M2 SMS).

**Sequencing:** sell S2 first. S2.5 is a much easier yes once he has watched a
state form fill itself, and pitching $14,000 of new scope in one breath will
stall a man who has already lost $120k.

---

## 9. Close

Ask for the S2 deposit **in the room**, while the demo is still on screen.
The single highest-conversion moment is the sixty seconds after he sees the
tracker link load on a phone.

> "S1's live and you just used it. S2 is the part nobody else in Florida has —
> upload a photo of a title, the state form fills itself. $4,750 starts it,
> $4,750 when I show it to you working. Want me to start Monday?"

Then stop talking. The first person to speak after a price loses.
