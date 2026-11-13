BEGIN;

ALTER TABLE announcements
  ADD COLUMN IF NOT EXISTS category VARCHAR(32) NOT NULL DEFAULT 'Informasi Umum',
  ADD COLUMN IF NOT EXISTS is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN IF NOT EXISTS expires_at TIMESTAMPTZ;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'announcements_category_check'
      AND conrelid = 'public.announcements'::regclass
  ) THEN
    ALTER TABLE announcements
      ADD CONSTRAINT announcements_category_check
      CHECK (category IN ('Akademik', 'Kemahasiswaan', 'Beasiswa', 'Kegiatan Kampus', 'Informasi Umum'));
  END IF;
END
$$;

CREATE INDEX IF NOT EXISTS announcements_active_order_idx
  ON announcements (is_pinned DESC, published_at DESC, expires_at)
  WHERE is_published = TRUE;

COMMIT;