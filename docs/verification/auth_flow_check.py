"""End-to-end authentication check in real Chromium. Run from the project root with the app, MySQL and Redis up.
Verification links are generated with a signed URL (the log mailer is not parsed), so email *content* is covered by PHP tests."""
import json, os, subprocess, time
from playwright.sync_api import sync_playwright

BASE = os.environ.get("CEFACOM_BASE", "http://localhost:8000")
AXE = os.environ.get("AXE_JS", "/tmp/axe/node_modules/axe-core/axe.min.js")
stamp = str(int(time.time()))
cust, staff = f"e2e.customer.{stamp}@example.com", f"e2e.staff.{stamp}@example.com"
PW = "correct-horse-9"

def tinker(code):
    out = subprocess.run(["php", "artisan", "tinker", "--execute=" + code], capture_output=True, text=True).stdout
    return out.strip().splitlines()[-1] if out.strip() else ""

results = {}
with sync_playwright() as p:
    b = p.chromium.launch()
    page = b.new_context(viewport={"width": 1280, "height": 800}).new_page()
    errors, expect_404 = [], {"on": False}
    page.on("pageerror", lambda e: errors.append(str(e)))
    # The browser logs a console error for any 404 page; the one we trigger on purpose (customer opening /admin) is expected.
    page.on("console", lambda m: errors.append(m.text) if m.type == "error" and not expect_404["on"] else None)

    # 1. Register
    page.goto(BASE + "/register", wait_until="networkidle")
    page.get_by_label("Full name").fill("E2E Customer")
    page.get_by_label("Business name (optional)").fill("E2E Ltd")
    page.get_by_label("Email address").fill(cust)
    page.get_by_label("Password", exact=True).fill(PW)
    page.get_by_label("Confirm password").fill(PW)
    page.get_by_role("button", name="Create account").click()
    page.wait_for_url("**/email/verify")
    results["register_lands_on_verify_notice"] = page.get_by_role("heading", name="Check your email").count() == 1

    # 2. Unverified customers are kept out of messaging areas but can see their overview
    page.goto(BASE + "/app/sms", wait_until="networkidle")
    results["unverified_blocked_from_sms"] = page.url.endswith("/email/verify")
    page.goto(BASE + "/app", wait_until="networkidle")
    results["unverified_sees_overview_with_warning"] = page.get_by_text("Verify your email address").count() == 1

    # 3. Verify through a signed link, then messaging areas open
    link = tinker("$u=App\\Models\\User::where('email','%s')->first(); echo Illuminate\\Support\\Facades\\URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id'=>$u->id,'hash'=>sha1($u->email)]);" % cust)
    page.goto(link, wait_until="networkidle")
    results["verified_lands_in_app"] = "/app" in page.url
    page.goto(BASE + "/app/sms", wait_until="networkidle")
    results["verified_can_open_sms"] = page.get_by_role("heading", name="SMS").count() >= 1 and "/app/sms" in page.url

    # 4. Account menu -> settings -> save profile
    page.get_by_role("button", name="Account menu for E2E Customer").click()
    page.get_by_role("link", name="Account settings").click()
    page.wait_for_url("**/app/settings")
    page.get_by_label("Phone number (optional)").fill("0803 123 4567")
    page.get_by_role("button", name="Save profile").click()
    page.get_by_text("Your profile was saved.").wait_for(timeout=5000)
    page.reload(wait_until="networkidle")
    results["profile_phone_saved_in_canonical_form"] = page.get_by_label("Phone number (optional)").input_value() == "+2348031234567"
    page.get_by_label("Phone number (optional)").fill("12345")
    page.get_by_role("button", name="Save profile").click()
    page.get_by_text("Enter a valid Nigerian mobile number").wait_for(timeout=5000)
    results["invalid_phone_shows_inline_error"] = True
    shots_ok = page.screenshot(path="/tmp/shots/settings.png", full_page=True) is not None

    # 5. Sign out, wrong password, right password
    page.get_by_role("button", name="Account menu for E2E Customer").click()
    page.get_by_role("button", name="Sign out", exact=True).click()
    page.wait_for_url(BASE + "/")
    page.goto(BASE + "/app", wait_until="networkidle")
    results["signed_out_redirected_to_login"] = page.url.endswith("/login")
    page.get_by_label("Email address").fill(cust)
    page.get_by_label("Password", exact=True).fill("wrong-password-1")
    page.get_by_role("button", name="Sign in").click()
    page.get_by_text("These credentials do not match our records.").wait_for(timeout=5000)
    results["wrong_password_shows_generic_error"] = True
    page.get_by_label("Password", exact=True).fill(PW)
    page.get_by_role("button", name="Sign in").click()
    page.wait_for_url("**/app")
    results["login_lands_in_customer_area"] = True

    # 6. Customers cannot see the admin area
    expect_404["on"] = True
    resp = page.goto(BASE + "/admin", wait_until="networkidle")
    expect_404["on"] = False
    results["customer_gets_404_on_admin"] = resp.status == 404
    page.goto(BASE + "/app", wait_until="networkidle")
    page.get_by_role("button", name="Account menu for E2E Customer").click()
    page.get_by_role("button", name="Sign out", exact=True).click()
    page.wait_for_url(BASE + "/")

    # 7. Staff: separate area, own navigation, kept out of /app
    tinker("$s=App\\Models\\User::factory()->superAdmin()->create(['name'=>'E2E Boss','email'=>'%s']); echo 'ok';" % staff)
    page.goto(BASE + "/login", wait_until="networkidle")
    page.get_by_label("Email address").fill(staff)
    page.get_by_label("Password", exact=True).fill(PW)
    page.get_by_role("button", name="Sign in").click()
    page.wait_for_url("**/admin")
    results["staff_login_lands_in_admin"] = True
    results["super_admin_sees_13_sections"] = page.locator("#primary-navigation nav a").count() == 13
    page.goto(BASE + "/app", wait_until="networkidle")
    results["staff_redirected_from_customer_area"] = page.url.endswith("/admin")
    page.goto(BASE + "/admin/account", wait_until="networkidle")
    results["staff_account_page_loads"] = page.get_by_role("heading", name="Account settings").count() == 1
    page.screenshot(path="/tmp/shots/admin_account.png")
    page.get_by_role("button", name="Account menu for E2E Boss").click()
    page.get_by_role("button", name="Sign out", exact=True).click()
    page.wait_for_url(BASE + "/")

    # 8. Accessibility (axe) on the new screens
    axe = open(AXE).read()
    def axe_run(path, label):
        page.goto(BASE + path, wait_until="networkidle"); page.add_script_tag(content=axe)
        v = page.evaluate("axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21a','wcag21aa','best-practice']}})")["violations"]
        results[f"axe_{label}"] = [(x["id"], len(x["nodes"])) for x in v] or "0 violations"
        for x in v:
            for n in x["nodes"][:2]: print("   AXE", label, x["id"], n["target"], (n.get("failureSummary") or "")[:140].replace("\n", " "))
    for path, label in [("/login", "login"), ("/register", "register"), ("/forgot-password", "forgot"), ("/reset-password/abc?email=a@b.co", "reset")]:
        axe_run(path, label)
    page.goto(BASE + "/login"); page.get_by_label("Email address").fill(cust); page.get_by_label("Password", exact=True).fill(PW); page.get_by_role("button", name="Sign in").click(); page.wait_for_url("**/app")
    axe_run("/app/settings", "settings"); axe_run("/app", "overview")
    page.goto(BASE + "/app", wait_until="networkidle"); page.add_script_tag(content=axe); page.get_by_role("button", name="Account menu for E2E Customer").click(); axe_run_menu = page.evaluate("axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa']}})")["violations"]
    results["axe_account_menu_open"] = [(x["id"], len(x["nodes"])) for x in axe_run_menu] or "0 violations"
    results["console_errors"] = errors or "none"
    b.close()

print(json.dumps(results, indent=1))
bad = {k: v for k, v in results.items() if v is False or (k.startswith("axe_") and v != "0 violations") or (k == "console_errors" and v != "none")}
print("PROBLEMS:", bad or "none")
