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
};
