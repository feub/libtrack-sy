export type ArtistType = {
  name: string;
};

export type CoverType = {
  formats: string;
};

export type ListReleasesType = {
  id?: string;
  title?: string;
  artists?: ArtistType[];
  cover?: CoverType[];
  release_date?: string;
  barcode?: string;
  format?: string;
  shelf?: string;
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
