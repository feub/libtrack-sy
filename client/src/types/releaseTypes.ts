export type ArtistType = {
  id: number;
  name: string;
  slug: string;
  thumbnail?: string;
};

export type GenreType = {
  id: number;
  name: string;
  slug: string;
};

export type ShelfType = {
  id: number;
  location: string;
  slug: string;
  description: string;
};

export type FormatType = {
  id: number;
  name: string;
  slug: string;
};

export type ListReleasesType = {
  id: number;
  title?: string;
  slug?: string;
  artists?: ArtistType[];
  cover?: string;
  release_date?: number;
  barcode?: number;
  format?: FormatType;
  shelf?: ShelfType;
  genres?: GenreType[];
  featured?: boolean;
  note?: string;
};

export type ScannedFormatType = {
  name: string;
};

export type ScannedImageType = {
  resource_url: string;
  type: string;
};

export type ScannedReleaseType = {
  id: number;
  artists: ArtistType[];
  styles: string[];
  title: string;
  year: number;
  formats: ScannedFormatType[];
  images: ScannedImageType[];
  uri: string;
};

export type ScannedResultType = {
  barcode: number;
  releases: ScannedReleaseType[];
};
