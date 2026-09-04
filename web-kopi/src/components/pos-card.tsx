import type { ReactNode } from "react";

export function POSCard({
  title,
  className = "",
  children,
}: {
  title?: string;
  className?: string;
  children: ReactNode;
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
