import { createFileRoute } from "@tanstack/react-router";
import { useMemo, useState } from "react";
import { Coffee, Plus } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { POSCard } from "@/components/pos-card";

export const Route = createFileRoute("/produk")({
  head: () => ({
    meta: [
      { title: "Produk — Kopi Nusantara POS" },
      { name: "description", content: "Kelola menu dan produk warung kopi." },
      { property: "og:title", content: "Produk — Kopi Nusantara POS" },
      { property: "og:description", content: "Daftar menu kopi, non-kopi, dan makanan." },
    ],
  }),
  component: ProdukPage,
});

const rupiah = (n: number) => "Rp" + n.toLocaleString("id-ID");

const categories = ["Semua", "Kopi", "Non-Kopi", "Makanan"];

const initialProducts = [
  { id: 1, name: "Kopi Susu Gula Aren", price: 22000, category: "Kopi", active: true },
  { id: 2, name: "Americano", price: 18000, category: "Kopi", active: true },
  { id: 3, name: "Cappuccino", price: 24000, category: "Kopi", active: true },
  { id: 4, name: "Teh Tarik", price: 16000, category: "Non-Kopi", active: true },
  { id: 5, name: "Matcha Latte", price: 26000, category: "Non-Kopi", active: false },
  { id: 6, name: "Roti Bakar", price: 15000, category: "Makanan", active: true },
  { id: 7, name: "French Fries", price: 17000, category: "Makanan", active: true },
  { id: 8, name: "Croissant", price: 20000, category: "Makanan", active: true },
];

function ProdukPage() {
  const [products, setProducts] = useState(initialProducts);
  const [activeCategory, setActiveCategory] = useState("Semua");
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ name: "", price: "", category: "Kopi" });

  const filtered = useMemo(() => {
    return activeCategory === "Semua" ? products : products.filter((p) => p.category === activeCategory);
  }, [products, activeCategory]);

  const summary = useMemo(() => {
    return { total: products.length, active: products.filter((p) => p.active).length };
  }, [products]);

  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.price) return;
    setProducts((prev) => [
      ...prev,
      {
        id: prev.length + 1,
        name: form.name,
        price: Number(form.price),
        category: form.category,
        active: true,
      },
    ]);
    setForm({ name: "", price: "", category: "Kopi" });
    setShowForm(false);
  };

  return (
    <main className="min-h-screen w-full bg-background px-3 pt-4 pb-28 sm:px-5 md:pb-6 md:pl-28 lg:px-8 lg:pl-32">
      <div className="flex min-w-0 flex-1 flex-col gap-4 sm:gap-6">
        <POSCard className="relative overflow-hidden">
          <h1 className="text-4xl text-primary-foreground sm:text-6xl">Produk</h1>
          <p className="mt-2 text-sm font-bold text-ink sm:text-base">Kelola menu & harga jual</p>
        </POSCard>

        <div className="grid gap-4 sm:grid-cols-3 sm:gap-6">
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Total produk</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">{summary.total}</p>
          </div>
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Produk aktif</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">{summary.active}</p>
          </div>
          <div className="brutal-lg rounded-3xl bg-primary p-4">
            <p className="text-sm font-bold text-primary-foreground">Kategori</p>
            <div className="my-2 h-[3px] w-4/5 bg-ink" />
            <p className="text-3xl font-extrabold text-cream">{categories.length - 1}</p>
          </div>
        </div>

        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-wrap gap-2">
            {categories.map((c) => (
              <button
                key={c}
                onClick={() => setActiveCategory(c)}
                className={`brutal rounded-full px-4 py-1.5 text-sm font-bold transition-transform ${
                  activeCategory === c ? "bg-cream text-ink" : "bg-primary text-primary-foreground hover:-translate-y-0.5"
                }`}
              >
                {c}
              </button>
            ))}
          </div>
          <Button onClick={() => setShowForm((s) => !s)} className="brutal bg-ink text-cream hover:bg-ink/90">
            <Plus className="size-4" />
            Tambah produk
          </Button>
        </div>

        {showForm && (
          <POSCard title="Tambah produk baru">
            <form onSubmit={handleAdd} className="grid gap-3 sm:grid-cols-4">
              <Input
                value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                placeholder="Nama produk"
                className="brutal border-ink bg-cream text-ink placeholder:text-ink/60 focus-visible:ring-ink sm:col-span-2"
              />
              <Input
                type="number"
                value={form.price}
                onChange={(e) => setForm((f) => ({ ...f, price: e.target.value }))}
                placeholder="Harga"
                className="brutal border-ink bg-cream text-ink placeholder:text-ink/60 focus-visible:ring-ink"
              />
              <select
                value={form.category}
                onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))}
                className="brutal h-9 rounded-md border-ink bg-cream px-3 text-sm font-bold text-ink focus:outline-none"
              >
                <option>Kopi</option>
                <option>Non-Kopi</option>
                <option>Makanan</option>
              </select>
              <div className="sm:col-span-4">
                <Button type="submit" className="brutal w-full bg-ink text-cream hover:bg-ink/90">
                  Simpan
                </Button>
              </div>
            </form>
          </POSCard>
        )}

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 sm:gap-6">
          {filtered.map((p) => (
            <div key={p.id} className="brutal-lg rounded-3xl bg-primary p-4">
              <div className="flex items-start justify-between">
                <div className="brutal flex size-12 items-center justify-center rounded-full bg-cream">
                  <Coffee className="size-6 text-ink" />
                </div>
                <span
                  className={`brutal rounded-full px-2 py-1 text-xs font-bold ${
                    p.active ? "bg-cream text-ink" : "bg-ink text-cream"
                  }`}
                >
                  {p.active ? "Aktif" : "Nonaktif"}
                </span>
              </div>
              <h3 className="mt-4 text-lg font-extrabold text-primary-foreground">{p.name}</h3>
              <p className="text-2xl font-extrabold text-cream">{rupiah(p.price)}</p>
              <p className="mt-1 text-xs font-bold text-ink">{p.category}</p>
            </div>
          ))}
        </div>
      </div>
    </main>
  );
}
