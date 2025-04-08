import { useRouteError } from "react-router";

export default function ErrorPage() {
  const error = useRouteError();
  console.error(error);

  return (
    <>
      <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10">
        <div className="flex w-full max-w-sm flex-col gap-6">
          <h1 className="text-3xl">Oops!</h1>
          <p>Sorry for this error.</p>
        </div>
      </div>
    </>
  );
}
