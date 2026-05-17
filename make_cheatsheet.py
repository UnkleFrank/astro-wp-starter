from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, HRFlowable
from reportlab.lib.enums import TA_LEFT, TA_CENTER

OUTPUT = r"D:\Sites\astro-wp-starter\Astro-WP-Starter-Setup-Guide.pdf"

# ── Colors ────────────────────────────────────────────────────────────────────
TEAL      = colors.HexColor("#1a8286")
DARK      = colors.HexColor("#1a1a2e")
LIGHT_BG  = colors.HexColor("#f0f9f9")
GRAY      = colors.HexColor("#6b7280")
BORDER    = colors.HexColor("#e5e7eb")
WHITE     = colors.white
CODE_BG   = colors.HexColor("#f1f5f9")
CODE_FG   = colors.HexColor("#0f4c52")

# ── Doc ───────────────────────────────────────────────────────────────────────
doc = SimpleDocTemplate(
    OUTPUT,
    pagesize=letter,
    leftMargin=0.6*inch,
    rightMargin=0.6*inch,
    topMargin=0.5*inch,
    bottomMargin=0.5*inch,
)

styles = getSampleStyleSheet()

# Custom styles
H1 = ParagraphStyle("H1", fontName="Helvetica-Bold", fontSize=22, textColor=WHITE,
                    spaceAfter=2, leading=26)
SUBTITLE = ParagraphStyle("SUBTITLE", fontName="Helvetica", fontSize=10,
                           textColor=colors.HexColor("#b2e0e2"), spaceAfter=0)
STEP_NUM = ParagraphStyle("STEP_NUM", fontName="Helvetica-Bold", fontSize=18,
                          textColor=TEAL, leading=22)
STEP_TITLE = ParagraphStyle("STEP_TITLE", fontName="Helvetica-Bold", fontSize=12,
                             textColor=DARK, leading=16, spaceAfter=3)
BODY = ParagraphStyle("BODY", fontName="Helvetica", fontSize=9,
                      textColor=colors.HexColor("#374151"), leading=14, spaceAfter=2)
CODE = ParagraphStyle("CODE", fontName="Courier-Bold", fontSize=8.5,
                      textColor=CODE_FG, leading=14, spaceAfter=0,
                      backColor=CODE_BG, leftIndent=8, rightIndent=8,
                      borderPadding=(5, 8, 5, 8))
NOTE = ParagraphStyle("NOTE", fontName="Helvetica-Oblique", fontSize=8,
                      textColor=GRAY, leading=12)
FOOTER = ParagraphStyle("FOOTER", fontName="Helvetica", fontSize=8,
                         textColor=GRAY, alignment=TA_CENTER)

story = []

# ── Header Banner ─────────────────────────────────────────────────────────────
header_data = [[
    Paragraph("Astro + WordPress Starter", H1),
    Paragraph("Six-step setup from clone to live site", SUBTITLE),
]]
header_table = Table(header_data, colWidths=[7.3*inch])
header_table.setStyle(TableStyle([
    ("BACKGROUND",   (0,0), (-1,-1), TEAL),
    ("TOPPADDING",   (0,0), (-1,-1), 14),
    ("BOTTOMPADDING",(0,0), (-1,-1), 14),
    ("LEFTPADDING",  (0,0), (-1,-1), 18),
    ("RIGHTPADDING", (0,0), (-1,-1), 18),
    ("ROWBACKGROUNDS",(0,0),(-1,-1),[TEAL]),
]))
story.append(header_table)
story.append(Spacer(1, 10))

# ── Steps ─────────────────────────────────────────────────────────────────────
steps = [
    (
        "1",
        "Clone the Starter",
        "Run these three commands in your terminal:",
        [
            "npx degit UnkleFrank/astro-wp-starter my-new-site",
            "cd my-new-site",
            "npm install",
        ],
        None,
    ),
    (
        "2",
        "Configure Environment",
        "Copy the example file, then edit it with two values:",
        [
            "cp .env.example .env",
        ],
        [
            ("SITE_URL", "https://the-new-site.com"),
            ("WP_URL",   "https://the-wordpress-site.com"),
        ],
    ),
    (
        "3",
        "Test Locally",
        "Starts a local dev server at http://localhost:4321 — pulls live content from WordPress.",
        [
            "npm run dev",
        ],
        None,
    ),
    (
        "4",
        "Install the Rebuild Plugin on WordPress",
        "Copy the plugin folder to WordPress, then activate it:",
        None,
        None,
        "Copy  wp-plugin/astro-rebuild/  →  /wp-content/plugins/astro-rebuild/\n"
        "Then activate in  WP Admin → Plugins",
    ),
    (
        "5",
        "Deploy to Cloudflare Pages",
        "Push project to GitHub, then connect in Cloudflare:",
        None,
        None,
        "Cloudflare Dashboard → Pages → Create a project → Connect to Git\n"
        "Build command: npm run build     Output directory: dist\n"
        "Add env vars: WP_URL and SITE_URL",
    ),
    (
        "6",
        "Wire Up Auto-Rebuild",
        "One-time setup so WordPress triggers a rebuild automatically on every save:",
        None,
        None,
        "Cloudflare Pages → Settings → Builds & deployments → Add deploy hook → copy URL\n"
        "WP Admin → Settings → Astro Rebuild → paste URL → Save",
    ),
]

col_w = [0.55*inch, 6.75*inch]

for step in steps:
    num, title, desc = step[0], step[1], step[2]
    commands   = step[3] if len(step) > 3 else None
    env_vars   = step[4] if len(step) > 4 else None
    plain_note = step[5] if len(step) > 5 else None

    # Right column content
    right = [Paragraph(title, STEP_TITLE), Paragraph(desc, BODY)]

    if commands:
        for cmd in commands:
            right.append(Paragraph(cmd, CODE))
        right.append(Spacer(1, 4))

    if env_vars:
        for key, val in env_vars:
            right.append(Paragraph(f"{key}={val}", CODE))
        right.append(Spacer(1, 4))

    if plain_note:
        for line in plain_note.split("\n"):
            right.append(Paragraph(line, NOTE))
        right.append(Spacer(1, 4))

    row_data = [[Paragraph(num, STEP_NUM), right]]
    row_table = Table(row_data, colWidths=col_w)
    row_table.setStyle(TableStyle([
        ("VALIGN",        (0,0), (-1,-1), "TOP"),
        ("TOPPADDING",    (0,0), (-1,-1), 8),
        ("BOTTOMPADDING", (0,0), (-1,-1), 2),
        ("LEFTPADDING",   (0,0), (0,-1),  10),
        ("RIGHTPADDING",  (0,0), (-1,-1), 6),
    ]))
    story.append(row_table)
    story.append(HRFlowable(width="100%", thickness=0.5, color=BORDER, spaceAfter=2))

# ── After Setup note ──────────────────────────────────────────────────────────
story.append(Spacer(1, 6))
done_data = [[
    Paragraph(
        "<b>That's it.</b>  From here, your content editor logs into WordPress, "
        "edits a page, hits Publish — Cloudflare rebuilds the site automatically "
        "in about 60 seconds. No code, no terminal, nothing else to do.",
        ParagraphStyle("done", fontName="Helvetica", fontSize=9,
                       textColor=DARK, leading=14)
    )
]]
done_table = Table(done_data, colWidths=[7.3*inch])
done_table.setStyle(TableStyle([
    ("BACKGROUND",    (0,0), (-1,-1), LIGHT_BG),
    ("TOPPADDING",    (0,0), (-1,-1), 10),
    ("BOTTOMPADDING", (0,0), (-1,-1), 10),
    ("LEFTPADDING",   (0,0), (-1,-1), 14),
    ("RIGHTPADDING",  (0,0), (-1,-1), 14),
    ("BOX",           (0,0), (-1,-1), 1, TEAL),
]))
story.append(done_table)

# ── Footer ────────────────────────────────────────────────────────────────────
story.append(Spacer(1, 10))
story.append(Paragraph(
    "github.com/UnkleFrank/astro-wp-starter  ·  MIT License",
    FOOTER
))

# ── Build ─────────────────────────────────────────────────────────────────────
doc.build(story)
print(f"Done. Saved: {OUTPUT}")
