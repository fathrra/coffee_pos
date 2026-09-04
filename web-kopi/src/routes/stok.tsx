import { createFileRoute } from "@tanstack/react-router";
import { useMemo, useState } from "react";
import { PackagePlus } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { POSCard } from "@/components/pos-card";

export const Route = createFileRoute("/stok")({
  head: () => ({
    meta: [
      { title: "Stok — Kopi Nusantara POS" },
      { name: "description", content: "Pantau dan kelola stok bahan baku warung kopi." },
      { property: "og:title", content: "Stok — Kopi Nusantara POS" },
      { property: "og:description", content: "Daftar stok, status menipis, dan form tambah stok." },
    ],
  }),
  component: StokPage,
});

const initialStock = [
  { name: "Biji kopi robusta", qty: 12, unit: "kg", min: 5 },
  { name: "Susu UHT", qty: 8, unit: "liter", min: 10 },
  { name: "Gula aren", qty: 3, unit: "kg", min: 5 },
  { name: "Cup 16oz", qty: 240, unit: "pcs", min: 50 },
  { name: "Es batu", qty: 2, unit: "karung", min: 5 },
  { name: "Syrup vanilla", qty: 0, unit: "botol", min: 2 },
  { name: "Kopi arabika", qty: 6, unit: "kg", min: 5 },
  { name: "Susu oat", qty: 4, unit: "liter", min: 5 },
];

function StokPage() {
  const [stock, setStock] = useState(initialStock);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ name: "", qty: "", unit: "" });

  const summary = useMemo(() => {
    const low = stock.filter((s) => s.qty > 0 && s.qty <= s.min).length;
    const out = stock.filter((s) => s.qty === 0).length;
    return { total: stock.length, low, out };
  }, [stock]);

  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.qty || !form.unit) return;
    setStock((prev) => [...prev, { name: form.name, qty: Number(form.qty), unit: form.unit, min: 0 }]);
    setForm({ name: "", qty: "", unit: "" });
    setShowForm(false);
  };

  return (
    <main className="min-h-screen w-full bg-background px-3 pt-4 pb-28 sm:px-5 md:pb-6 md:pl-28 lg:px-8 lg:pl-32">
      <div className="flex min-w-0 flex-1 flex-col gap-4 sm:gap-6">
        <POSCard className="relative overflow-hidden">
          <h1 className="text-4xl text-primary-foreground sm:text-6xl">Stok</h1>
          <p className="mt-2 text-sm font-bold text-ink sm:text-base">
            Pantau bahan baku & stok menipis
          </p>
        </POSCard>

        <div className="grid gap-4 sm:grid-cols-3 sm:gap-6">
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Total item</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">{summary.total}</p>
          </div>
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Stok menipis</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">{summary.low}</p>
          </div>
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Stok habis</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">{summary.out}</p>
          </div>
        </div>

        <div className="flex justify-end">
          <Button
            onClick={() => setShowForm((s) => !s)}
            className="brutal bg-ink text-cream hover:bg-ink/90"
          >
            <PackagePlus className="size-4" />
            Tambah stok
          </Button>
        </div>

        {showForm && (
          <POSCard title="Tambah stok baru">
            <form onSubmit={handleAdd} className="grid gap-3 sm:grid-cols-4">
              <Input
                value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                placeholder="Nama bahan"
                className="brutal border-ink bg-cream text-ink placeholder:text-ink/60 focus-visible:ring-ink sm:col-span-2"
              />
              <Input
                type="number"
                value={form.qty}
                onChange={(e) => setForm((f) => ({ ...f, qty: e.target.value }))}
                placeholder="Jumlah"
                className="brutal border-ink bg-cream text-ink placeholder:text-ink/60 focus-visible:ring-ink"
              />
              <Input
                value={form.unit}
                onChange={(e) => setForm((f) => ({ ...f, unit: e.target.value }))}
                placeholder="Satuan"
                className="brutal border-ink bg-cream text-ink placeholder:text-ink/60 focus-visible:ring-ink"
              />
              <div className="sm:col-span-4">
                <Button type="submit" className="brutal w-full bg-ink text-cream hover:bg-ink/90">
                  Simpan
                </Button>
              </div>
            </form>
          </POSCard>
        )}

        <POSCard title="Daftar stok">
          <ul>
            {stock.map((s) => {
              const status = s.qty === 0 ? "habis" : s.qty <= s.min ? "menipis" : "aman";
              return (
                <li
                  key={s.name}
                  className="flex items-center justify-between border-b-2 border-ink/70 py-3 text-sm font-bold text-cream"
                >
                  <span>{s.name}</span>
                  <div className="flex items-center gap-3">
                    <span className={status === "habis" ? "text-destructive" : status === "menipis" ? "text-ink" : ""}>
                      {s.qty} {s.unit}
                    </span>
                    <span
                      className={`brutal inline-block rounded-full px-2 py-1 text-xs ${
                        status === "habis"
                          ? "bg-destructive text-destructive-foreground"
                          : status === "menipis"
                            ? "bg-cream text-ink"
                            : "bg-cream/70 text-ink"
                      }`}
                    >
                      {status}
                    </span>
                  </div>
                </li>
              );
            })}
          </ul>
        </POSCard>
      </div>
    </main>
  );
}
