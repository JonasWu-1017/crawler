import _axios from "axios"

const axios = (baseURL) => {
    //建立一個自定義的axios
    const instance = _axios.create({
            baseURL: baseURL || 'http://localhost:8000', //JSON-Server端口位置
            timeout: 30000,
            async:true,
            crossDomain:true,
            headers: { 
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Methods': '*',
                'Access-Control-Allow-Headers': '*',
            },
        });

     return instance;
}

export {axios};
export default axios();