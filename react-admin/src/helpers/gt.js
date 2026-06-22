/*
    Functión gt (gesimatic translation)

    params: 
        string => string used as key of translation, exe: 'reset_registration'
        fallback => string returned bu default if translations no exists

    return
        string => translated or fallback or key

    Cometido: This function receives a string key to searh in window.gesimaticAdmin?.translations?[key], 
              if exist the translation is returned if not return fallback if is setted if not returns the key.
*/

export const gt = (key, fallback = '') => {
    return window.gesimaticBackupAdmin?.translations?.[key] 
        || fallback 
        || key;
}