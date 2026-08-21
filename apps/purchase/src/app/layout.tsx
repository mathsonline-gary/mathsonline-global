import type { Metadata } from "next";
import { Geist_Mono, Instrument_Sans } from "next/font/google";

import "./globals.css";
import { cn } from "@/lib/utils/cn";

const instrumentSans = Instrument_Sans({
  subsets: ["latin"],
  variable: "--font-sans",
});

const geistMono = Geist_Mono({
  subsets: ["latin"],
  variable: "--font-geist-mono",
});

/**
 * Placeholder only. The per-market title, description and the Organization JSON-LD belong to the
 * `[market]` segment, because the root layout cannot know which market is being served.
 */
export const metadata: Metadata = {
  title: "Purchase",
};

/**
 * The brand-blue canvas every purchase page sits on, ported from membership's
 * `layouts/app.blade.php`. It is a literal rather than a design token: it is this one application's
 * chrome, not a colour the design system offers.
 *
 * No theme provider and no dark mode. `globals.css` carries a `.dark` token set, but nothing adds
 * that class, and a hard-coded blue canvas would not survive it anyway.
 */
export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html
      lang="en"
      className={cn(
        "h-full font-sans antialiased",
        instrumentSans.variable,
        geistMono.variable,
      )}
    >
      <body className="flex min-h-full flex-col bg-[#0576c6] text-foreground">
        {children}
      </body>
    </html>
  );
}
