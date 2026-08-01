# Rapid Rebuilt — Demo Script

**Internal.** Credentials and links live in `DEMO-ACCESS.md`.

The rehearsal *is* the setup. Run the flow once in practice, leave the documents
unreviewed, and the live demo starts exactly where it should.

---

## Before he arrives (~15 minutes)

**1. Wipe it clean**

```bash
rr-reset
```

Works from any folder. Open a new terminal tab first so the shortcut is loaded.

**2. Confirm the customer password works**

Log in as `dev@local.test` right now. You set that password at signup.

**3. Run the full flow once**

Sign in as the customer → Start New Case → enter the vehicle → add both
components → upload the five PDFs from `~/Desktop/rapid-rebuilt-demo-docs` →
Submit.

**Stop there. Leave the documents pending.** That unreviewed state is the
demo's starting position.

**4. Open two windows**

| Window | Role | Account |
|---|---|---|
| Normal | Staff | `staff@rapidrebuilt.com` |
| Private / Incognito | Customer | `dev@local.test` |

Two separate windows are required — the browser can only hold one session at a time.

**5. Phone out**, unlocked, ready for the tracking link.

You should now have one case sitting in **Submitted** with five pending documents.

---

## The demo (~12 minutes)

### Act 1 — What the customer sees (3 min)

Share the **customer window**, on the case page.

> "This is what your customer sees. Their vehicle, the parts they replaced,
> every document they sent, and exactly where they stand."

Point at, in this order:
- **VIN and vehicle details** — entered once, never re-typed
- **Major Components** — Hood with the donor VIN, Front Bumper with the invoice
  number. Mention this is what the state requires on the 84491.
- **Documents** — all five, uploaded from a phone
- **Case Timeline** — every action stamped automatically

### Act 2 — Your side of the desk (4 min)

Switch to the **staff window** → open the case.

> "Same case, your side."

- Click **View** on a document — the PDF opens from private storage
- **Approve** three of them
- **Reject** one. Type this reason:

> Photo is too blurry to read the VIN. Please re-upload a clearer scan.

Say out loud: *"That reason goes straight to the customer. No phone call."*

### Act 3 — The loop (2 min) — **the moment that sells it**

Switch to the customer window and **reload**.

> "Watch. No email, no phone call."

The rejected document shows in red with your reason and a **Re-upload** button.
Pause here. Let it land.

Back to staff → **Update Status → Documents Complete** → Update.

Reload the customer window → **Schedule Inspection** has appeared. Book a slot:
pick a weekday from tomorrow onward, choose a time, confirm.

### Act 4 — The tracker (1 min)

On the customer case page, click **Share Tracking Link**. Paste it into your
phone. Hold the phone up.

> "No login, no account. This is what your customer texts the guy buying the car."

### Act 5 — Roles (1 min)

> "Your inspector sees his assigned jobs. He does not see your revenue."

Log in as Dave (`inspector@rapidrebuilt.com`) if you want to prove it on screen.

---

## Say the gaps before he finds them

| Gap | What to say |
|---|---|
| **Payments fail** | "Stripe's connected, running in test mode until you hand me your live account. Ten-minute switch." |
| **No emails send** | "Email service goes live at launch. Everything's already wired for it." |
| **No custom domain** | "Point your domain at this whenever you're ready — about an hour, including SSL." |

Announced reads as planning. Discovered reads as broken.

**Do not click the payment button during the demo.**

---

## The close — with the screen still up

Ask while the tracker is still on your phone. That is the highest-conversion
moment of the entire meeting.

> "That's Stage 1, and it's live — you just used it. Stage 2 is the part nobody
> in Florida has: your customer photographs their salvage title and the 82040
> fills itself in. $4,750 starts it, $4,750 when I show it to you working.
> Want me to start Monday?"

**Then stop talking.** The first person to speak after a price loses.

---

## Two rules

1. **Never fix anything live in front of him.** Write it down, move on.
2. **Never click into a screen you didn't rehearse.**
