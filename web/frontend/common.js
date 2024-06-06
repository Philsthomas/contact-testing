export async function handleResponseErrors(message, setToastProps, setIsLoading) {
        setIsLoading(false);
        setToastProps({ content: message, error: true });
}

