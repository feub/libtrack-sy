import { useRouteError } from "react-router";

export default function ErrorPage() {
  const error = useRouteError();
  console.error(error);

  return (
    <>
      <h1 className="text-3xl">Oops!</h1>
      <p>Sorry for this error.</p>
    </>
  );
}
