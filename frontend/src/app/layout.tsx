import type { Metadata } from "next";
import "./globals.css";
import { Navbar } from "@/components/Navbar";

export const metadata: Metadata = {
  title: "IntentGuard — Transaction Integrity & Merchant Accountability",
  description: "AI-assisted transaction authorization, purchase integrity, and merchant accountability platform.",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className="h-full antialiased font-sans">
      <body className="min-h-full flex flex-col bg-[#F3F6FB] text-[#101D35]">
        <Navbar />
        {children}
      </body>
    </html>
  );
}

