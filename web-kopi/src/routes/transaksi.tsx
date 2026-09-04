import { createFileRoute } from "@tanstack/react-router";
import { useMemo, useState } from "react";
import { Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { POSCard } from "@/components/pos-card";

export const Route = createFileRoute("/transaksi")({
  head: () => ({
    meta: [
      { title: "Transaksi — Kopi Nusantara POS" },
      { name: "description", content: "Kelola riwayat transaksi warung kopi." },
      { property: "og:title", content: "Transaksi — Kopi Nusantara POS" },
      {
        property: "og:description",
        content: "Riwayat transaksi, pencarian invoice, dan ringkasan penjualan.",
      },
    ],
  }),
  component: TransaksiPage,
});

const rupiah = (n: number) => "Rp" + n.toLocaleString("id-ID");

const allTransactions = [
  { invoice: "INV-0912", kasir: "Fathur", item: 3, total: 32000, waktu: "08.42", status: "Selesai", method: "Cash" },
  { invoice: "INV-0913", kasir: "Fathur", item: 2, total: 21000, waktu: "09.15", status: "Selesai", method: "QRIS" },
  { invoice: "INV-0914", kasir: "Dewi", item: 5, total: 27000, waktu: "10.03", status: "Selesai", method: "Cash" },
  { invoice: "INV-0915", kasir: "Dewi", item: 1, total: 20000, waktu: "11.30", status: "Refund", method: "QRIS" },
  { invoice: "INV-0916", kasir: "Fathur", item: 4, total: 45000, waktu: "12.10", status: "Selesai", method: "Cash" },
  { invoice: "INV-0917", kasir: "Dewi", item: 2, total: 18000, waktu: "13.45", status: "Selesai", method: "Cash" },
  { invoice: "INV-0918", kasir: "Fathur", item: 6, total: 54000, waktu: "15.20", status: "Selesai", method: "QRIS" },
  { invoice: "INV-0919", kasir: "Dewi", item: 1, total: 15000, waktu: "16.05", status: "Selesai", method: "Cash" },
];

function TransaksiPage() {
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("Semua");

  const filtered = useMemo(() => {
    return allTransactions.filter((t) => {
      const matchesSearch =
        t.invoice.toLowerCase().includes(search.toLowerCase()) ||
        t.kasir.toLowerCase().includes(search.toLowerCase());
      const matchesStatus = statusFilter === "Semua" || t.status === statusFilter;
      return matchesSearch && matchesStatus;
    });
  }, [search, statusFilter]);

  const totals = useMemo(() => {
    const sales = filtered.reduce((s, t) => s + t.total, 0);
    return { sales, count: filtered.length };
  }, [filtered]);

  return (
    <main className="min-h-screen w-full bg-background px-3 pt-4 pb-28 sm:px-5 md:pb-6 md:pl-28 lg:px-8 lg:pl-32">
      <div className="flex min-w-0 flex-1 flex-col gap-4 sm:gap-6">
        <POSCard className="relative overflow-hidden">
          <h1 className="text-4xl text-primary-foreground sm:text-6xl">Transaksi</h1>
          <p className="mt-2 text-sm font-bold text-ink sm:text-base">
            Riwayat & pencarian invoice harian
          </p>
        </POSCard>

        <div className="grid gap-4 sm:grid-cols-3 sm:gap-6">
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Total transaksi</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">{totals.count}</p>
          </div>
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Total penjualan</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">{rupiah(totals.sales)}</p>
          </div>
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Rata-rata</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">
              {rupiah(totals.count ? Math.round(totals.sales / totals.count) : 0)}
            </p>
          </div>
        </div>

        <POSCard title="Riwayat transaksi">
          <div className="mb-4 flex flex-col gap-3 sm:flex-row">
            <div className="relative flex-1">
              <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink" />
              <Input
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Cari invoice atau kasir..."
                className="brutal border-ink bg-cream pl-9 text-ink placeholder:text-ink/60 focus-visible:ring-ink"
              />
            </div>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="brutal h-9 rounded-md border-ink bg-cream px-3 text-sm font-bold text-ink focus:outline-none"
            >
              <option>Semua</option>
              <option>Selesai</option>
              <option>Refund</option>
            </select>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[640px] text-left">
              <thead>
                <tr className="border-b-[3px] border-ink text-primary-foreground">
                  {["Invoice", "Waktu", "Kasir", "Item", "Total", "Metode", "Status"].map((h) => (
                    <th key={h} className="pb-2 text-sm font-extrabold">
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filtered.map((t) => (
                  <tr key={t.invoice} className="border-b-2 border-ink/70 text-cream">
                    <td className="py-3 text-sm font-bold">{t.invoice}</td>
                    <td className="py-3 text-sm font-bold">{t.waktu}</td>
                    <td className="py-3 text-sm font-bold">{t.kasir}</td>
                    <td className="py-3 text-sm font-bold">{t.item}</td>
                    <td className="py-3 text-sm font-bold">{rupiah(t.total)}</td>
                    <td className="py-3 text-sm font-bold">{t.method}</td>
                    <td className="py-3 text-sm font-bold">
                      <span
                        className={`brutal inline-block rounded-full px-2 py-1 text-xs ${
                          t.status === "Selesai" ? "bg-cream text-ink" : "bg-ink text-cream"
                        }`}
                      >
                        {t.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {filtered.length === 0 && (
            <p className="mt-4 text-center text-sm font-bold text-cream">
              Tidak ada transaksi ditemukan.
            </p>
          )}
        </POSCard>
      </div>
    </main>
  );
}
