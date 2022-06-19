import React from "react";
import '../../css/app.css';
import axios from "../components/axios";

class Home extends React.Component
{
    GET = async() => {
        try 
        {
            const Data = await axios.get("/api/screenshot?force=1&url=https://google.com/"); 
            console.log(Data.data);
            alert("GET success!!");
        } 
        catch (error) 
        {
            console.log(error);
            alert("GET Error!!"); 
        }  
    };
    
    render()
    {
        return (
            <div className="App">
                <header className="App-header">
                    <div className="box">
                        <input
                            name="firstName"
                            maxLength="256"
                            size="64"
                        />
                        <label>
                            <input type="checkbox" />
                            froce crawl to get the newest result
                        </label>
                    </div>
                    <div>
                        <button onClick={this.GET}>try to crawl</button>
                    </div>
                </header>
            </div>
        );
    };
};

export default Home;