import { Eye, EyeOff } from "lucide-react";

export const PasswordVisibilityIcon = ({
  showPassword,
  switchPasswordVisibility,
}: {
  showPassword: boolean;
  switchPasswordVisibility: () => void;
}) => {
  const IconComponent = showPassword ? EyeOff : Eye;

  return (
    <IconComponent
      className="absolute right-2 top-1/2 transform -translate-y-1/2 text-neutral-500 z-10"
      onClick={switchPasswordVisibility}
    />
  );
};
