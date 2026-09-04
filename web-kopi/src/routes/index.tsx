import { createFileRoute } from "@tanstack/react-router";
import { useMemo } from "react";
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import mascotAsset from "../assets/mascot.png.asset.json";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "Dashboard Kasir — Kopi Nusantara POS" },
      {
        name: "description",
        content:
          "Pantau penjualan harian, keuntungan, grafik mingguan, transaksi, stok, dan produk terlaris warung kopi Anda dalam satu dashboard.",
      },
      { property: "og:title", content: "Dashboard Kasir — Kopi Nusantara POS" },
      {
        property: "og:description",
        content:
          "Penjualan hari ini, grafik mingguan, transaksi, stok, dan produk terlaris dalam satu layar.",
      },
    ],
  }),
  component: Dashboard,
});

const rupiah = (n: number) => "Rp" + n.toLocaleString("id-ID");

const weekly = [
  { day: "SEN", total: 185000 },
  { day: "SEL", total: 272000 },
  { day: "RAB", total: 193000 },
  { day: "KAM", total: 281000 },
  { day: "JUM", total: 240000 },
  { day: "SAB", total: 172000 },
  { day: "MIN", total: 288000 },
];

const transactions = [
  { invoice: "INV-0912", kasir: "Fathur", item: 3, total: 32000, waktu: "08.42" },
  { invoice: "INV-0913", kasir: "Fathur", item: 2, total: 21000, waktu: "09.15" },
  { invoice: "INV-0914", kasir: "Dewi", item: 5, total: 27000, waktu: "10.03" },
  { invoice: "INV-0915", kasir: "Dewi", item: 1, total: 20000, waktu: "11.30" },
];

const stock = [
  { name: "Biji kopi robusta", qty: 12, unit: "kg" },
  { name: "Susu UHT", qty: 8, unit: "liter" },
  { name: "Gula aren", qty: 3, unit: "kg" },
  { name: "Cup 16oz", qty: 240, unit: "pcs" },
  { name: "Es batu", qty: 2, unit: "karung" },
];

const bestSellers = [
  { name: "Kopi Susu Gula Aren", sold: 42 },
  { name: "Americano", sold: 27 },
  { name: "Cappuccino", sold: 21 },
  { name: "Teh Tarik", sold: 16 },
  { name: "Roti Bakar", sold: 11 },
];

function Card({
  title,
  className = "",
  children,
}: {
  title?: string;
  className?: string;
  children: React.ReactNode;
}) {
  return (
    <section className={`brutal-lg rounded-3xl bg-primary p-5 sm:p-6 ${className}`}>
      {title ? (
        <h2 className="mb-4 text-xl text-primary-foreground sm:text-2xl">{title}</h2>
      ) : null}
      {children}
    </section>
  );
}

function Dashboard() {
  const totals = useMemo(() => {
    const sales = transactions.reduce((s, t) => s + t.total, 0);
    const items = transactions.reduce((s, t) => s + t.item, 0);
    return { sales, items, profit: Math.round(sales * 0.5) };
  }, []);

  return (
    <main className="min-h-screen w-full bg-background px-3 pt-4 pb-28 sm:px-5 md:pb-6 md:pl-28 lg:px-8 lg:pl-32">
      <div className="flex w-full gap-4 sm:gap-6">
        <div className="flex min-w-0 flex-1 flex-col gap-4 sm:gap-6">
          <Card className="relative overflow-hidden">
            <div className="max-w-[62%]">
              <h1 className="text-4xl text-primary-foreground sm:text-6xl">Hi Fathur</h1>
              <p className="mt-2 text-sm font-bold text-ink sm:text-base">
                Have you drinked Coffee yet?
              </p>
            </div>
            <img
              src={mascotAsset.url}
              alt="Maskot barista kopi"
              width={816}
              height={816}
              className="pointer-events-none absolute -right-2 -bottom-6 h-[140%] w-auto max-w-[45%] object-contain sm:-bottom-10"
            />
          </Card>

          <div className="grid gap-4 sm:grid-cols-3 sm:gap-6">
            {[
              { label: "Total penjualan hari ini", value: rupiah(totals.sales), note: `${transactions.length} transaksi` },
              { label: "Produk terjual", value: `${totals.items} item`, note: "sejak pukul 08.30" },
              { label: "Total keuntungan hari ini", value: rupiah(totals.profit), note: "untuk hari ini" },
            ].map((s) => (
              <div key={s.label} className="brutal-lg rounded-3xl bg-primary p-4">
                <p className="text-sm font-bold text-primary-foreground">{s.label}</p>
                <div className="my-2 h-[3px] w-4/5 bg-ink" />
                <p className="text-3xl font-extrabold text-cream">{s.value}</p>
                <p className="mt-2 text-xs font-bold text-ink">{s.note}</p>
              </div>
            ))}
          </div>

          <Card title="Grafik penjualan">
            <div className="h-72 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={weekly} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                  <CartesianGrid stroke="var(--ink)" vertical={false} />
                  <XAxis
                    dataKey="day"
                    stroke="var(--ink)"
                    tick={{ fill: "var(--cream)", fontSize: 12, fontWeight: 700, fontStyle: "italic" }}
                    tickLine={false}
                  />
                  <YAxis
                    stroke="var(--ink)"
                    tickFormatter={(v: number) => `${v / 1000}K`}
                    tick={{ fill: "var(--cream)", fontSize: 11, fontWeight: 700, fontStyle: "italic" }}
                    tickLine={false}
                    width={44}
                  />
                  <Tooltip
                    cursor={{ fill: "var(--muted)", opacity: 0.35 }}
                    contentStyle={{
                      background: "var(--cream)",
                      border: "3px solid var(--ink)",
                      borderRadius: 12,
                      fontWeight: 700,
                      fontStyle: "italic",
                      color: "var(--ink)",
                    }}
                    formatter={(v: number) => [rupiah(v), "Penjualan"]}
                  />
                  <Bar dataKey="total" radius={[14, 14, 0, 0]} maxBarSize={46}>
                    {weekly.map((d, i) => (
                      <Cell key={d.day} fill={i % 2 === 1 ? "var(--ink)" : "var(--cream)"} />
                    ))}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>
          </Card>

          <Card title="Transaksi hari ini">
            <div className="overflow-x-auto">
              <table className="w-full min-w-[520px] text-left">
                <thead>
                  <tr className="border-b-[3px] border-ink text-primary-foreground">
                    {["Invoice", "Kasir", "Item", "Total", "Waktu"].map((h) => (
                      <th key={h} className="pb-2 text-sm font-extrabold">
                        {h}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {transactions.map((t) => (
                    <tr key={t.invoice} className="border-b-2 border-ink/70 text-cream">
                      <td className="py-3 text-sm font-bold">{t.invoice}</td>
                      <td className="py-3 text-sm font-bold">{t.kasir}</td>
                      <td className="py-3 text-sm font-bold">{t.item}</td>
                      <td className="py-3 text-sm font-bold">{rupiah(t.total)}</td>
                      <td className="py-3 text-sm font-bold">{t.waktu}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>

          <div className="grid gap-4 sm:gap-6 lg:grid-cols-[3fr_2fr]">
            <Card title="Informasi stok">
              <ul>
                {stock.map((s) => (
                  <li
                    key={s.name}
                    className="flex items-center justify-between border-b-2 border-ink/70 py-3 text-sm font-bold text-cream"
                  >
                    <span>{s.name}</span>
                    <span className={s.qty <= 3 ? "text-ink" : ""}>
                      {s.qty} {s.unit}
                    </span>
                  </li>
                ))}
              </ul>
            </Card>

            <Card title="Terlaris">
              <ul>
                {bestSellers.map((b, i) => (
                  <li
                    key={b.name}
                    className="flex items-center justify-between border-b-2 border-ink/70 py-3 text-sm font-bold text-cream"
                  >
                    <span>
                      {i + 1}. {b.name}
                    </span>
                    <span>{b.sold}x</span>
                  </li>
                ))}
              </ul>
            </Card>
          </div>
        </div>
      </div>
    </main>
  );
}
