export type ArtistType = {
  id: number;
  name: string;
  slug: string;
  thumbnail?: string;
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
  title: string;
  year: number;
  formats: ScannedFormatType[];
  images: ScannedImageType[];
};

export type ScannedResultType = {
  barcode: number;
  releases: ScannedReleaseType[];
};
