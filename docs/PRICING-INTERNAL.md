# Rapid Rebuilt — Internal Pricing Strategy

**Not for the client.** This is your negotiation map: what to ask, what to concede,
where the floor is, and what to never give away.

---

## 1. The number you ask for

**$19,000 build, staged in four payments. $950/mo ongoing after launch.**

Do not present this as one number. Present it as four small decisions, because
he is cash-sensitive (his words: "practically bankrupt… $120k+ in" on a previous
attempt). Four $3k–$9k decisions are psychologically survivable. One $19k decision is not.

| Stage | Price | Deposit to start | Balance on demo |
|---|---|---|---|
| S1 — Portal live | $3,500 | $1,750 | $1,750 |
| S2 — AI + official state forms | $9,500 | $4,750 | $4,750 |
| M1 — Marketing foundation | $3,500 | $1,750 | $1,750 |
| M2 — Outreach engine | $2,500 | $1,250 | $1,250 |
| **Total** | **$19,000** | | |
| Ongoing | $950/mo | starts after M2 | |

---

## 2. What this is actually worth (your leverage)

An outside shop quoting this scope — three-role portal, payments, AI document
extraction, state-form automation, marketing site — lands at **$55k–$80k** and
3–5 months. Even an offshore team is ~$20k–$35k with heavy management risk and
no accountability.

**You are pricing at roughly 65–70% below market.** That discount is your single
biggest lever. Name it explicitly, once, then never discount again. A discount
that keeps moving isn't a discount — it's an admission the price was fake.

**Say this:** *"Market for this is $55k–$80k. You're at $19k because of who you are.
That's the friend price, and it's the only one I've got."*

---

## 3. Your walk-away floors

| Line | Floor | Reasoning |
|---|---|---|
| S1 | $3,500 | Already delivered. Non-negotiable — the work exists. |
| S2 | $8,000 | Hard floor. Below this the AI + form automation isn't worth building. |
| M1 | $3,000 | |
| M2 | $2,000 | Droppable entirely if he wants to self-serve outreach. |
| Monthly | $750 | Below $750 you're paying for his hosting and AI out of pocket. |
| **Build floor** | **$16,000** | Walk below this. |

**Never concede:**
- **The 50% deposit.** Nothing starts on a handshake. This is the whole rule.
- **Ownership before final payment.** Code transfers when the stage is paid.
- **Free "just one more thing" scope.** Every add-on gets quoted separately.

---

## 4. Concession ladder (give in this order, only if pushed)

1. **Stretch the timeline**, not the price — "same $19k, spread over 5 months"
2. **Drop M2** ($2,500 off) — he does his own outreach; portal is unaffected
3. **Defer the monthly** to 60 days post-launch instead of immediately
4. **Split S2 into two payments** of $2,375 each instead of two of $4,750

Notice all four preserve the rate. You're selling time and scope, never price.

---

## 5. Objection handling

**"That's a lot of money right now."**
> "It's $1,750 to start. Not $19,000. You see a working portal before decision two."

**"I already spent $120k and got nothing."**
> "Exactly why we're doing it in stages. You've already seen S1 live — you're not
> paying on faith anymore, you're paying on something you clicked through yourself."

**"Can you do it cheaper?"**
> "The market number is $55k–$80k. I'm at $19k. I can reduce scope, not the rate —
> if M2 is out, that's $2,500 off and you handle your own dealer outreach."

**"What's the $950/mo actually for?"**
> Point at the itemized table in the client doc. Hosting, AI usage, SMS, security
> patches, campaign ops, fixes with no hourly billing, priority support.
> It's a service contract, not a subscription to nothing.

**"Can I pay after it makes money?"**
> No. Politely, but no. Revenue-share means you carry his business risk for free.
> If he pushes hard, the answer is a longer schedule — never contingent payment.

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

## 8. Close

Ask for the S2 deposit **in the room**, while the demo is still on screen.
The single highest-conversion moment is the sixty seconds after he sees the
tracker link load on a phone.

> "S1's live and you just used it. S2 is the part nobody else in Florida has —
> upload a photo of a title, the state form fills itself. $4,750 starts it,
> $4,750 when I show it to you working. Want me to start Monday?"

Then stop talking. The first person to speak after a price loses.
