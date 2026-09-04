import { createFileRoute } from "@tanstack/react-router";
import { ChevronRight, LogOut, Printer, ReceiptText, Store, User } from "lucide-react";
import { POSCard } from "@/components/pos-card";

export const Route = createFileRoute("/pengaturan")({
  head: () => ({
    meta: [
      { title: "Pengaturan — Kopi Nusantara POS" },
      { name: "description", content: "Pengaturan akun kasir, info warung, dan preferensi aplikasi." },
      { property: "og:title", content: "Pengaturan — Kopi Nusantara POS" },
      { property: "og:description", content: "Profil kasir, info warung, dan pengaturan aplikasi." },
    ],
  }),
  component: PengaturanPage,
});

const settingsItems = [
  { icon: Printer, label: "Printer & nota" },
  { icon: ReceiptText, label: "Pajak & layanan" },
  { icon: Store, label: "Info warung" },
];

function PengaturanPage() {
  return (
    <main className="min-h-screen w-full bg-background px-3 pt-4 pb-28 sm:px-5 md:pb-6 md:pl-28 lg:px-8 lg:pl-32">
      <div className="flex min-w-0 flex-1 flex-col gap-4 sm:gap-6">
        <POSCard className="relative overflow-hidden">
          <h1 className="text-4xl text-primary-foreground sm:text-6xl">Pengaturan</h1>
          <p className="mt-2 text-sm font-bold text-ink sm:text-base">Atur akun, warung, dan preferensi</p>
        </POSCard>

        <POSCard title="Profil kasir">
          <div className="flex items-center gap-4">
            <div className="brutal flex size-16 items-center justify-center rounded-full bg-cream">
              <User className="size-8 text-ink" />
            </div>
            <div>
              <h2 className="text-2xl font-extrabold text-primary-foreground">Fathur</h2>
              <p className="text-sm font-bold text-ink">Kasir utama</p>
            </div>
          </div>
          <div className="mt-4 grid gap-3 sm:grid-cols-2">
            <div className="brutal rounded-2xl bg-cream p-3">
              <p className="text-xs font-bold text-ink/70">Email</p>
              <p className="text-sm font-extrabold text-ink">fathur@kopi.com</p>
            </div>
            <div className="brutal rounded-2xl bg-cream p-3">
              <p className="text-xs font-bold text-ink/70">Telepon</p>
              <p className="text-sm font-extrabold text-ink">+62 812-3456-7890</p>
            </div>
          </div>
        </POSCard>

        <POSCard title="Info warung">
          <div className="space-y-3">
            <div className="brutal rounded-2xl bg-cream p-3">
              <p className="text-xs font-bold text-ink/70">Nama warung</p>
              <p className="text-sm font-extrabold text-ink">Kopi Nusantara</p>
            </div>
            <div className="brutal rounded-2xl bg-cream p-3">
              <p className="text-xs font-bold text-ink/70">Alamat</p>
              <p className="text-sm font-extrabold text-ink">Jl. Merdeka No. 21, Jakarta</p>
            </div>
            <div className="brutal rounded-2xl bg-cream p-3">
              <p className="text-xs font-bold text-ink/70">Nomor telepon</p>
              <p className="text-sm font-extrabold text-ink">+62 21-9876-5432</p>
            </div>
          </div>
        </POSCard>

        <POSCard title="Preferensi">
          <ul className="space-y-2">
            {settingsItems.map((item) => (
              <li key={item.label}>
                <button className="flex w-full items-center justify-between brutal rounded-2xl bg-cream p-3 text-left transition-transform hover:-translate-y-0.5">
                  <div className="flex items-center gap-3">
                    <item.icon className="size-5 text-ink" />
                    <span className="text-sm font-extrabold text-ink">{item.label}</span>
                  </div>
                  <ChevronRight className="size-4 text-ink" />
                </button>
              </li>
            ))}
            <li>
              <button className="flex w-full items-center justify-between brutal rounded-2xl bg-destructive p-3 text-left transition-transform hover:-translate-y-0.5">
                <div className="flex items-center gap-3">
                  <LogOut className="size-5 text-destructive-foreground" />
                  <span className="text-sm font-extrabold text-destructive-foreground">Keluar</span>
                </div>
                <ChevronRight className="size-4 text-destructive-foreground" />
              </button>
            </li>
          </ul>
        </POSCard>
      </div>
    </main>
  );
}
