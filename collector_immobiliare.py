from playwright.sync_api import sync_playwright
from playwright_stealth import stealth

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)  # O True se vuoi headless
        context = browser.new_context()
        page = context.new_page()

        # Applica stealth per evitare il rilevamento
        stealth(page)

        # Ora puoi navigare normalmente
        page.goto("https://www.immobiliare.it")
        # Il resto del tuo codice per il scraping...

        browser.close()

if __name__ == "__main__":
    main()
